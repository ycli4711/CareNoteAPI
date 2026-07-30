<?php

namespace App\Services\Ai;

use App\Models\AiChannel;
use InvalidArgumentException;

class AiAdapterRegistry
{
    public function adapterFor(AiChannel $channel, string $capability): string
    {
        $adapter = config("ai.adapters.{$channel->provider_type}.{$capability}.provider");

        if (! is_string($adapter) || $adapter === '') {
            throw new InvalidArgumentException(
                "渠道类型 {$channel->provider_type} 暂不支持场景请求类型 {$capability}。",
            );
        }

        return $adapter;
    }

    public function defaultEndpointFor(AiChannel $channel, string $capability): string
    {
        $endpoint = config("ai.adapters.{$channel->provider_type}.{$capability}.endpoint");

        if (! is_string($endpoint) || $endpoint === '') {
            throw new InvalidArgumentException(
                "渠道类型 {$channel->provider_type} 未配置场景请求类型 {$capability} 的默认 Endpoint。",
            );
        }

        return $endpoint;
    }

    public function supports(AiChannel $channel, string $capability): bool
    {
        return is_string(
            config("ai.adapters.{$channel->provider_type}.{$capability}.provider"),
        );
    }
}
