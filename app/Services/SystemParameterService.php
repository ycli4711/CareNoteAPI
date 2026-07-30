<?php

namespace App\Services;

use App\Models\SystemParameter;

class SystemParameterService
{
    public function string(string $key, string $default = ''): string
    {
        $value = SystemParameter::query()->where('key', $key)->value('value');

        return is_string($value) ? $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = SystemParameter::query()->where('key', $key)->value('value');

        return is_numeric($value) ? (int) $value : $default;
    }

    /** @return array<string, mixed> */
    public function json(string $key, array $default = []): array
    {
        $value = SystemParameter::query()->where('key', $key)->value('value');

        if (! is_string($value) || $value === '') {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public function set(string $key, mixed $value, string $type, string $group, ?string $description = null): void
    {
        SystemParameter::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->serialize($value, $type),
                'value_type' => $type,
                'group' => $group,
                'description' => $description,
            ],
        );
    }

    private function serialize(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
