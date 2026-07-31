<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('cn_family_members')]
#[Fillable([
    'family_id',
    'name',
    'relation',
    'avatar',
    'birthday',
    'allergies',
    'chronic_diseases',
    'linked_user_openid',
])]
class FamilyMember extends Model
{
    use HasUlids;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'immutable_datetime',
            'allergies' => 'array',
            'chronic_diseases' => 'array',
        ];
    }
}
