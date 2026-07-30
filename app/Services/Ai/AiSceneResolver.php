<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiProcessingException;
use App\Models\AiSceneModel;

class AiSceneResolver
{
    public function __construct(private readonly AiAdapterRegistry $adapters) {}

    /** @return list<AiProviderConfig> */
    public function configs(string $sceneCode): array
    {
        $scene = config("ai.scenes.{$sceneCode}");

        if (! is_array($scene)) {
            throw new AiProcessingException("未知AI业务场景：{$sceneCode}");
        }

        return AiSceneModel::query()
            ->with('channel')
            ->where('scene_code', $sceneCode)
            ->where('enabled', true)
            ->whereHas('channel', fn ($query) => $query->where('enabled', true))
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(fn (AiSceneModel $route): ?AiProviderConfig => $this->fromRoute(
                $route,
                (string) $scene['capability'],
            ))
            ->filter()
            ->values()
            ->all();
    }

    private function fromRoute(AiSceneModel $route, string $capability): ?AiProviderConfig
    {
        $channel = $route->channel;

        if ($channel === null || blank($channel->api_key) || blank($channel->base_url)) {
            return null;
        }

        $options = is_array($channel->options) ? $channel->options : [];

        return new AiProviderConfig(
            aiSceneModelId: $route->getKey(),
            provider: $this->adapters->adapterFor($channel, $capability),
            model: $route->model,
            apiKey: $channel->api_key,
            baseUrl: $channel->base_url,
            endpoint: $this->stringOption(
                $options,
                'endpoint',
                $this->adapters->defaultEndpointFor($channel, $capability),
            ),
            timeout: $this->intOption($options, 'timeout', $channel->timeout),
            options: $options,
            channelCode: $channel->code,
        );
    }

    /** @param array<string, mixed> $options */
    private function stringOption(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /** @param array<string, mixed> $options */
    private function intOption(array $options, string $key, int $default): int
    {
        $value = $options[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }
}
