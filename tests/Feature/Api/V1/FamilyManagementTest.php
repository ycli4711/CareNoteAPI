<?php

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;

function familyApiUser(string $openId, array $attributes = []): array
{
    $user = User::factory()->create($attributes);
    $user->identities()->create([
        'provider' => 'wechat_mini_program',
        'provider_subject' => $openId,
    ]);

    return [
        $user,
        $user->createToken('mini-program', ['app:access'])->plainTextToken,
    ];
}

function familyRecord(
    string $creatorOpenId,
    array $memberOpenIds,
    string $name = '我的家庭',
): Family {
    return Family::query()->create([
        'name' => $name,
        'creator_openid' => $creatorOpenId,
        'member_openids' => $memberOpenIds,
    ]);
}

test('account user can list only active families they joined', function (): void {
    [$currentUser, $token] = familyApiUser('openid-current', [
        'display_name' => '张三',
        'avatar_url' => 'https://example.com/avatar.png',
    ]);
    [$otherUser] = familyApiUser('openid-other', ['display_name' => '李四']);

    $family = familyRecord('openid-current', ['openid-current', 'openid-other']);
    familyRecord('openid-current', ['openid-current'], '已解散家庭')
        ->update(['dissolved_at' => now()]);
    familyRecord('openid-stranger', ['openid-stranger'], '其他家庭');

    $response = $this->withToken($token)->getJson('/api/v1/families');

    $response
        ->assertOk()
        ->assertJsonPath('data.items.0.id', (string) $family->getKey())
        ->assertJsonPath('data.items.0.member_count', 2)
        ->assertJsonPath('data.items.0.account_members.0.id', (string) $currentUser->getKey())
        ->assertJsonPath('data.items.0.account_members.0.is_current_user', true)
        ->assertJsonPath('data.items.0.account_members.0.is_creator', true)
        ->assertJsonPath('data.items.0.account_members.1.id', (string) $otherUser->getKey())
        ->assertJsonPath('data.items.0.account_members.1.is_creator', false)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonMissing(['creator_openid' => 'openid-current'])
        ->assertJsonMissing(['member_openids' => ['openid-current', 'openid-other']]);
});

test('account user can create a family and their self profile in one transaction', function (): void {
    [$user, $token] = familyApiUser('openid-current', [
        'display_name' => ' 张三 ',
        'avatar_url' => 'https://example.com/avatar.png',
    ]);

    $response = $this->withToken($token)->postJson('/api/v1/families', [
        'name' => '  我的家庭  ',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('code', 'COMMON.CREATED')
        ->assertJsonPath('data.name', '我的家庭')
        ->assertJsonPath('data.member_count', 1)
        ->assertJsonPath('data.account_members.0.id', (string) $user->getKey())
        ->assertJsonPath('data.account_members.0.is_current_user', true)
        ->assertJsonPath('data.account_members.0.is_creator', true);

    $family = Family::query()->sole();

    expect($family->member_openids)->toBe(['openid-current'])
        ->and($family->creator_openid)->toBe('openid-current');

    $this->assertDatabaseHas('cn_family_members', [
        'family_id' => $family->getKey(),
        'name' => ' 张三 ',
        'relation' => '本人',
        'avatar' => 'https://example.com/avatar.png',
        'linked_user_openid' => 'openid-current',
    ]);
});

test('family name validation trims whitespace and enforces length', function (): void {
    [, $token] = familyApiUser('openid-current');

    $this->withToken($token)
        ->postJson('/api/v1/families', ['name' => '   '])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COMMON.VALIDATION_FAILED')
        ->assertJsonValidationErrors('name');

    $this->withToken($token)
        ->postJson('/api/v1/families', ['name' => str_repeat('家', 21)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('any family account member can update its name', function (): void {
    [, $memberToken] = familyApiUser('openid-member');
    $family = familyRecord('openid-creator', ['openid-creator', 'openid-member']);

    $this->withToken($memberToken)
        ->postJson("/api/v1/families/{$family->getKey()}", ['name' => '  新名称  '])
        ->assertOk()
        ->assertJsonPath('data.name', '新名称');

    expect($family->refresh()->name)->toBe('新名称');
});

test('non-members cannot update a family name', function (): void {
    [, $token] = familyApiUser('openid-stranger');
    $family = familyRecord('openid-creator', ['openid-creator']);

    $this->withToken($token)
        ->postJson("/api/v1/families/{$family->getKey()}", ['name' => '越权名称'])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH.FORBIDDEN');

    expect($family->refresh()->name)->toBe('我的家庭');
});

test('leaving unlinks the self profile and transfers ownership when needed', function (): void {
    [, $creatorToken] = familyApiUser('openid-creator');
    familyApiUser('openid-member');
    $family = familyRecord('openid-creator', ['openid-creator', 'openid-member']);
    $profile = FamilyMember::query()->create([
        'family_id' => $family->getKey(),
        'name' => '创建者',
        'relation' => '本人',
        'linked_user_openid' => 'openid-creator',
    ]);

    $this->withToken($creatorToken)
        ->postJson("/api/v1/families/{$family->getKey()}/leave")
        ->assertOk()
        ->assertJsonPath('data.family_id', (string) $family->getKey())
        ->assertJsonPath('data.family_dissolved', false);

    $family->refresh();

    expect($family->member_openids)->toBe(['openid-member'])
        ->and($family->creator_openid)->toBe('openid-member')
        ->and($family->dissolved_at)->toBeNull()
        ->and($profile->refresh()->linked_user_openid)->toBeNull();
});

test('the last account leaving dissolves the family without deleting medical data', function (): void {
    [, $token] = familyApiUser('openid-current');
    $family = familyRecord('openid-current', ['openid-current']);
    $profile = FamilyMember::query()->create([
        'family_id' => $family->getKey(),
        'name' => '本人',
        'relation' => '本人',
        'linked_user_openid' => 'openid-current',
    ]);

    $this->withToken($token)
        ->postJson("/api/v1/families/{$family->getKey()}/leave")
        ->assertOk()
        ->assertJsonPath('data.family_dissolved', true);

    expect($family->refresh()->dissolved_at)->not->toBeNull()
        ->and($family->member_openids)->toBe([])
        ->and($profile->refresh()->exists)->toBeTrue()
        ->and($profile->linked_user_openid)->toBeNull();

    $this->withToken($token)
        ->postJson("/api/v1/families/{$family->getKey()}", ['name' => '不能修改'])
        ->assertNotFound();
});

test('non-members cannot leave and missing families return not found', function (): void {
    [, $token] = familyApiUser('openid-current');
    $family = familyRecord('openid-other', ['openid-other']);

    $this->withToken($token)
        ->postJson("/api/v1/families/{$family->getKey()}/leave")
        ->assertForbidden();

    $this->withToken($token)
        ->postJson('/api/v1/families/missing-family/leave')
        ->assertNotFound();
});
