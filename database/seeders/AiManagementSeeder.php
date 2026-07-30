<?php

namespace Database\Seeders;

use App\Models\AiChannel;
use App\Models\SystemParameter;
use Illuminate\Database\Seeder;

class AiManagementSeeder extends Seeder
{
    public function run(): void
    {
        AiChannel::query()->firstOrCreate(
            ['code' => 'qwen'],
            [
                'name' => '阿里云百炼',
                'provider_type' => 'qwen',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'timeout' => 30,
                'enabled' => true,
            ],
        );

        $this->parameter(
            'ai.quota.policy',
            json_encode(config('ai.quota_policy'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'json',
            'AI场景基础额度、早鸟额度、邀请奖励与用药单邀请阶梯。',
        );
    }

    private function parameter(string $key, string $value, string $type, string $description): void
    {
        SystemParameter::query()->firstOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'value_type' => $type,
                'group' => 'ai_quota',
                'description' => $description,
            ],
        );
    }
}
