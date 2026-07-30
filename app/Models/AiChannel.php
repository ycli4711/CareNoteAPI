<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'provider_type', 'api_key', 'base_url', 'timeout', 'enabled', 'options'])]
class AiChannel extends Model
{
    use HasUlids;

    protected $table = 'cn_ai_channels';

    /** @return HasMany<AiSceneModel, $this> */
    public function sceneModels(): HasMany
    {
        return $this->hasMany(AiSceneModel::class);
    }

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'timeout' => 'integer',
            'enabled' => 'boolean',
            'options' => 'array',
        ];
    }
}
