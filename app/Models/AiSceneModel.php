<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ai_channel_id', 'scene_code', 'model', 'priority', 'enabled'])]
class AiSceneModel extends Model
{
    use HasUlids;

    protected $table = 'cn_ai_scene_models';

    /** @return BelongsTo<AiChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(AiChannel::class, 'ai_channel_id');
    }

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
