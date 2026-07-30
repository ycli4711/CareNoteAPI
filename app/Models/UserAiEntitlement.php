<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'early_bird', 'successful_invites', 'bonuses', 'synced_at'])]
class UserAiEntitlement extends Model
{
    use HasUlids;

    protected $table = 'cn_user_ai_entitlements';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'early_bird' => 'boolean',
            'successful_invites' => 'integer',
            'bonuses' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
