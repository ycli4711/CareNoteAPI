<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'scene_code',
    'request_id',
    'idempotency_key',
    'status',
    'quota_status',
    'final_ai_scene_model_id',
    'duration_ms',
    'error_message',
    'result_summary',
])]
class AiRequest extends Model
{
    use HasUlids;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $table = 'cn_ai_requests';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AiCallLog, $this> */
    public function callLogs(): HasMany
    {
        return $this->hasMany(AiCallLog::class);
    }

    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'result_summary' => 'array',
        ];
    }
}
