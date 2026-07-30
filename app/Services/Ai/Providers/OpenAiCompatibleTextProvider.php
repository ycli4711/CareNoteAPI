<?php

namespace App\Services\Ai\Providers;

use App\Exceptions\Ai\AiProcessingException;
use App\Services\Ai\AiProviderConfig;
use App\Services\Ai\AiTextResult;
use App\Services\Ai\Contracts\TextProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiCompatibleTextProvider implements TextProvider
{
    public function complete(array $messages, AiProviderConfig $config): AiTextResult
    {
        $parameters = Arr::only($config->options, [
            'temperature',
            'top_p',
            'max_tokens',
            'response_format',
        ]);

        if ($config->provider === 'qwen_text') {
            $parameters['enable_thinking'] = false;
        }

        try {
            $response = Http::baseUrl(rtrim($config->baseUrl, '/'))
                ->withToken($config->apiKey)
                ->acceptJson()
                ->timeout($config->timeout)
                ->post(ltrim($config->endpoint, '/'), [
                    'model' => $config->model,
                    'messages' => $messages,
                    ...$parameters,
                ]);
        } catch (ConnectionException $exception) {
            throw new AiProcessingException('AI供应商连接失败。', previous: $exception);
        } catch (Throwable $exception) {
            throw new AiProcessingException('AI供应商请求异常。', previous: $exception);
        }

        if (! $response->successful()) {
            throw new AiProcessingException("AI供应商返回异常状态：{$response->status()}");
        }

        $payload = $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiProcessingException('AI供应商未返回有效文本。');
        }

        return new AiTextResult(
            content: $content,
            inputTokens: $this->nullableInt(data_get($payload, 'usage.prompt_tokens')),
            outputTokens: $this->nullableInt(data_get($payload, 'usage.completion_tokens')),
            raw: is_array($payload) ? $payload : [],
        );
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
