<?php

return [
    'capabilities' => [
        'text_generation' => '文本理解与生成',
        'vision' => '视觉理解',
        'speech_recognition' => '语音识别',
        'speech_synthesis' => '语音合成',
    ],

    'adapters' => [
        'qwen' => [
            'text_generation' => [
                'provider' => 'qwen_text',
                'endpoint' => '/chat/completions',
            ],
        ],
        'openai' => [
            'text_generation' => [
                'provider' => 'openai_text',
                'endpoint' => '/chat/completions',
            ],
        ],
    ],

    'scenes' => [
        'assistant_chat' => [
            'name' => '智能助手',
            'capability' => 'text_generation',
            'description' => '意图识别、关怀回复、症状归类和计划字段提取。',
        ],
        'medicine_ocr' => [
            'name' => '药品识别',
            'capability' => 'text_generation',
            'description' => '将OCR文本整理为结构化药品信息。',
        ],
        'voice_plan' => [
            'name' => '语音智能添加',
            'capability' => 'text_generation',
            'description' => '将语音识别文本整理为用药计划草稿。',
        ],
        'medication_sheet' => [
            'name' => '用药单导入',
            'capability' => 'text_generation',
            'description' => '将用药单OCR文本整理为多个药品和提醒草稿。',
        ],
    ],

    'quota_policy' => [
        'period' => 'monthly',
        'scenes' => [
            'assistant_chat' => ['default_limit' => 30, 'early_bird_limit' => 100],
            'medicine_ocr' => ['default_limit' => 10, 'early_bird_limit' => 40],
            'voice_plan' => ['default_limit' => 10, 'early_bird_limit' => 40],
            'medication_sheet' => ['default_limit' => 1, 'early_bird_limit' => 1],
        ],
        'referral_rewards' => [
            'friend_login' => [
                'name' => '好友完成登录',
                'scene' => 'assistant_chat',
                'inviter_amount' => 5,
                'invitee_amount' => 2,
            ],
            'friend_first_medicine' => [
                'name' => '好友添加首个药品',
                'scene' => 'medicine_ocr',
                'inviter_amount' => 3,
                'invitee_amount' => 1,
            ],
            'friend_first_plan' => [
                'name' => '好友创建首个计划',
                'scene' => 'assistant_chat',
                'inviter_amount' => 10,
                'invitee_amount' => 3,
            ],
            'friend_streak_3' => [
                'name' => '好友连续记录3天',
                'scene' => 'voice_plan',
                'inviter_amount' => 3,
                'invitee_amount' => 1,
            ],
        ],
        'medication_sheet_tiers' => [
            ['min_invites' => 1, 'limit' => 2],
            ['min_invites' => 3, 'limit' => 5],
        ],
    ],
];
