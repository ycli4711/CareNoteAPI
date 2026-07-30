<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_request_id',
    'ai_scene_model_id',
    'scene_code',
    'channel_code',
    'model',
    'status',
    'attempt',
    'duration_ms',
    'input_tokens',
    'output_tokens',
    'error_message',
    'result_summary',
])]
class AiCallLog extends Model
{
    use HasUlids;

    protected $table = 'cn_ai_call_logs';

    /** @return BelongsTo<AiRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'ai_request_id');
    }

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'result_summary' => 'array',
        ];
    }
}
