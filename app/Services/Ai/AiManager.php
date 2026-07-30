<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiProcessingException;
use App\Models\AiCallLog;
use App\Models\AiRequest;
use App\Models\User;
use App\Services\Ai\Contracts\TextProvider;
use App\Services\Ai\Providers\OpenAiCompatibleTextProvider;
use Illuminate\Support\Str;
use Throwable;

class AiManager
{
    public function __construct(
        private readonly AiSceneResolver $resolver,
        private readonly AiQuotaService $quota,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $context
     */
    public function complete(
        string $sceneCode,
        array $messages,
        ?User $user = null,
        ?string $idempotencyKey = null,
        array $context = [],
    ): AiTextResult {
        if (! is_array(config("ai.scenes.{$sceneCode}"))) {
            throw new AiProcessingException("未知AI业务场景：{$sceneCode}");
        }

        $configs = $this->resolver->configs($sceneCode);
        if ($configs === []) {
            throw new AiProcessingException("AI业务场景未配置可用模型：{$sceneCode}");
        }

        $this->guardIdempotency($sceneCode, $user, $idempotencyKey);
        $request = $this->createRequest($sceneCode, $user, $idempotencyKey);
        $startedAt = microtime(true);
        $charged = false;

        try {
            if ($user instanceof User) {
                $this->quota->consume($user, $sceneCode);
                $charged = true;
                $request->update(['quota_status' => 'charged']);
            }

            $lastException = null;

            foreach ($configs as $index => $config) {
                $attemptStartedAt = microtime(true);

                try {
                    $result = $this->provider($config->provider)->complete($messages, $config);
                    $this->logAttempt($request, $sceneCode, $config, $index + 1, $attemptStartedAt, $result);
                    $request->update([
                        'status' => AiRequest::STATUS_SUCCEEDED,
                        'final_ai_scene_model_id' => $config->aiSceneModelId,
                        'duration_ms' => $this->durationMs($startedAt),
                        'result_summary' => [
                            'input_tokens' => $result->inputTokens,
                            'output_tokens' => $result->outputTokens,
                            'content_length' => mb_strlen($result->content),
                            ...$this->safeContext($context),
                        ],
                    ]);

                    return $result;
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $this->logAttempt(
                        $request,
                        $sceneCode,
                        $config,
                        $index + 1,
                        $attemptStartedAt,
                        null,
                        $exception->getMessage(),
                    );

                }
            }

            throw new AiProcessingException(
                $lastException?->getMessage() ?: 'AI服务暂不可用。',
                previous: $lastException,
            );
        } catch (Throwable $exception) {
            if ($charged && $user instanceof User) {
                $this->quota->refund($user, $sceneCode);
            }

            $request->update([
                'status' => AiRequest::STATUS_FAILED,
                'quota_status' => $charged ? 'refunded' : 'not_charged',
                'duration_ms' => $this->durationMs($startedAt),
                'error_message' => Str::limit($exception->getMessage(), 500, ''),
            ]);

            throw $exception;
        }
    }

    private function provider(string $provider): TextProvider
    {
        return match ($provider) {
            'qwen_text', 'openai_text' => app(OpenAiCompatibleTextProvider::class),
            default => throw new AiProcessingException("未知AI文本供应商：{$provider}"),
        };
    }

    private function guardIdempotency(string $sceneCode, ?User $user, ?string $idempotencyKey): void
    {
        if (! $user instanceof User || blank($idempotencyKey)) {
            return;
        }

        $exists = AiRequest::query()
            ->where('user_id', $user->getKey())
            ->where('scene_code', $sceneCode)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($exists) {
            throw new AiProcessingException('该AI请求已提交，请勿重复操作。');
        }
    }

    private function createRequest(string $sceneCode, ?User $user, ?string $idempotencyKey): AiRequest
    {
        return AiRequest::query()->create([
            'user_id' => $user?->getKey(),
            'scene_code' => $sceneCode,
            'request_id' => (string) Str::ulid(),
            'idempotency_key' => filled($idempotencyKey) ? $idempotencyKey : null,
            'status' => AiRequest::STATUS_PROCESSING,
            'quota_status' => 'not_charged',
        ]);
    }

    private function logAttempt(
        AiRequest $request,
        string $sceneCode,
        AiProviderConfig $config,
        int $attempt,
        float $startedAt,
        ?AiTextResult $result = null,
        ?string $errorMessage = null,
    ): void {
        AiCallLog::query()->create([
            'ai_request_id' => $request->getKey(),
            'ai_scene_model_id' => $config->aiSceneModelId,
            'scene_code' => $sceneCode,
            'channel_code' => $config->channelCode,
            'model' => $config->model,
            'status' => $result instanceof AiTextResult ? 'succeeded' : 'failed',
            'attempt' => $attempt,
            'duration_ms' => $this->durationMs($startedAt),
            'input_tokens' => $result?->inputTokens,
            'output_tokens' => $result?->outputTokens,
            'error_message' => filled($errorMessage) ? Str::limit($errorMessage, 500, '') : null,
            'result_summary' => $result instanceof AiTextResult
                ? ['content_length' => mb_strlen($result->content)]
                : null,
        ]);
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /** @param array<string, mixed> $context
     * @return array<string, scalar|null>
     */
    private function safeContext(array $context): array
    {
        return collect($context)
            ->filter(fn (mixed $value, string $key): bool => in_array($key, ['source', 'feature'], true)
                && (is_scalar($value) || $value === null))
            ->all();
    }
}
