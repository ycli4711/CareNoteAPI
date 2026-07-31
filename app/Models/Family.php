<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Table('cn_families')]
#[Fillable([
    'name',
    'creator_openid',
    'member_openids',
    'invite_code',
    'invite_code_expires',
    'dissolved_at',
])]
class Family extends Model
{
    use HasUlids;

    private ?string $resourceCurrentOpenId = null;

    /** @var Collection<int, User>|null */
    private ?Collection $resourceAccountUsers = null;

    /** @return HasMany<FamilyMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id');
    }

    /**
     * @param  Collection<int, User>  $accountUsers
     */
    public function setResourceContext(string $currentOpenId, Collection $accountUsers): self
    {
        $this->resourceCurrentOpenId = $currentOpenId;
        $this->resourceAccountUsers = $accountUsers;

        return $this;
    }

    public function resourceCurrentOpenId(): ?string
    {
        return $this->resourceCurrentOpenId;
    }

    /** @return Collection<int, User> */
    public function resourceAccountUsers(): Collection
    {
        return $this->resourceAccountUsers ?? collect();
    }

    /**
     * Exclude dissolved families from route model binding.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->whereNull('dissolved_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'member_openids' => 'array',
            'invite_code_expires' => 'immutable_datetime',
            'dissolved_at' => 'immutable_datetime',
        ];
    }
}
