<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{comment: string, columns: array<string, string>}>
     */
    private const TABLES = [
        'cn_families' => [
            'comment' => '家庭档案',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'name' => '家庭名称',
                'creator_openid' => '创建者微信 OpenID',
                'member_openids' => '家庭成员微信 OpenID 列表',
                'invite_code' => '家庭邀请码',
                'invite_code_expires' => '家庭邀请码过期时间',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_family_members' => [
            'comment' => '家庭成员档案',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'name' => '成员姓名',
                'relation' => '与家庭创建者的关系',
                'avatar' => '成员头像地址',
                'birthday' => '出生日期',
                'allergies' => '过敏史',
                'chronic_diseases' => '慢性病信息',
                'linked_user_openid' => '关联用户的微信 OpenID',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_medicines' => [
            'comment' => '家庭药品档案',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'name' => '药品名称',
                'specification' => '药品规格',
                'manufacturer' => '生产厂家',
                'expiry_date' => '有效期截止时间',
                'opened_date' => '开封时间',
                'opened_validity' => '开封后有效天数',
                'stock' => '当前库存数量',
                'stock_unit' => '库存单位',
                'stock_threshold' => '库存预警阈值',
                'stock_alert_silenced_until' => '库存预警静默截止时间',
                'stock_alert_never' => '是否永久关闭库存预警',
                'stock_alert_last_sent_at' => '最近一次库存预警发送时间',
                'stock_debt' => '库存欠账数量',
                'photo_urls' => '药品图片地址列表',
                'cover_photo_url' => '药品封面图片地址',
                'notes' => '药品说明',
                'remark' => '备注',
                'symptom_categories' => '适用症状分类',
                'age_group' => '适用年龄段',
                'gender_suitable' => '适用性别',
                'expiry_alert_dismissed' => '是否忽略有效期预警',
                'expiry_alert_dismissed_at' => '忽略有效期预警的时间',
                'version' => '当前药品版本号',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_medicine_versions' => [
            'comment' => '药品库存与批次版本记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'medicine_id' => '关联药品 ID',
                'version_number' => '药品版本号',
                'stock' => '该版本库存数量',
                'expiry_date' => '该版本有效期截止时间',
                'opened_date' => '该版本开封时间',
                'opened_validity' => '开封后有效天数',
                'batch_number' => '药品批号',
                'name' => '药品名称快照',
                'specification' => '药品规格快照',
                'manufacturer' => '生产厂家快照',
                'change_reason' => '版本变更原因',
                'is_current' => '是否为当前版本',
                'is_expired' => '是否已过期',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_medication_plans' => [
            'comment' => '用药计划',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'creator_openid' => '计划创建者微信 OpenID',
                'member_id' => '用药家庭成员 ID',
                'medicine_id' => '关联药品 ID',
                'plan_name' => '用药计划名称',
                'dosage' => '单次用药剂量',
                'dosage_unit' => '剂量单位',
                'frequency' => '用药频率',
                'remind_times' => '每日提醒时间列表',
                'start_date' => '计划开始时间',
                'end_date' => '计划结束时间',
                'before_meal' => '是否饭前服用',
                'remark' => '用药备注',
                'is_active' => '计划是否启用',
                'source_visit_id' => '来源就诊记录 ID',
                'alarm_setup_prompted_at' => '最近一次提示设置闹钟的时间',
                'alarm_setup_count' => '提示设置闹钟次数',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_medication_records' => [
            'comment' => '用药执行记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'member_id' => '用药家庭成员 ID',
                'medicine_id' => '关联药品 ID',
                'plan_id' => '关联用药计划 ID',
                'scheduled_time' => '计划用药时间',
                'actual_time' => '实际用药时间',
                'status' => '用药状态',
                'dosage' => '本次用药剂量',
                'notes' => '用药备注',
                'voice_url' => '语音记录地址',
                'consumed_versions' => '消耗的药品版本及数量',
                'stock_shortage' => '库存不足信息',
                'created_at' => '创建时间',
            ],
        ],
        'cn_health_logs' => [
            'comment' => '健康日志',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'member_id' => '关联家庭成员 ID',
                'log_type' => '日志类型',
                'content' => '日志内容',
                'media_url' => '媒体文件地址',
                'related_records' => '关联业务记录',
                'created_at' => '创建时间',
            ],
        ],
        'cn_inventory_records' => [
            'comment' => '药品盘点记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'inventory_date' => '盘点时间',
                'operator_openid' => '盘点操作人微信 OpenID',
                'operator_name' => '盘点操作人姓名',
                'medicine_id' => '关联药品 ID',
                'medicine_name' => '药品名称快照',
                'status' => '盘点状态',
                'changes' => '库存变更明细',
                'note' => '盘点备注',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_visits' => [
            'comment' => '就诊记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '记录所属用户微信 OpenID',
                'family_id' => '所属家庭 ID',
                'member_id' => '就诊家庭成员 ID',
                'visit_date' => '就诊时间',
                'visit_type' => '就诊类型',
                'hospital' => '就诊医院',
                'department' => '就诊科室',
                'doctor' => '接诊医生',
                'symptoms' => '症状描述',
                'diagnosis_note' => '诊断记录',
                'doctor_advice' => '医生建议',
                'follow_up_note' => '复诊备注',
                'lab_reports' => '检查报告列表',
                'previous_visit_id' => '上一次就诊记录 ID',
                'follow_up_date' => '计划复诊时间',
                'follow_up_reminded' => '是否已发送复诊提醒',
                'linked_plan_ids' => '关联用药计划 ID 列表',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_follow_up_subscriptions' => [
            'comment' => '复诊订阅消息记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '订阅用户微信 OpenID',
                'family_id' => '所属家庭 ID',
                'visit_id' => '关联就诊记录 ID',
                'member_id' => '关联家庭成员 ID',
                'follow_up_date' => '计划复诊时间',
                'symptoms_snapshot' => '症状描述快照',
                'hospital_snapshot' => '医院名称快照',
                'member_name_snapshot' => '成员姓名快照',
                'tmpl_id' => '微信订阅消息模板 ID',
                'status' => '消息发送状态',
                'sent_at' => '消息发送时间',
                'error_msg' => '发送失败信息',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_alarm_setup_logs' => [
            'comment' => '用药闹钟设置日志',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'family_id' => '所属家庭 ID',
                'plan_id' => '关联用药计划 ID',
                'member_id' => '关联家庭成员 ID',
                'openid' => '操作用户微信 OpenID',
                'status' => '闹钟设置状态',
                'error_message' => '设置失败信息',
                'created_at' => '创建时间',
            ],
        ],
        'cn_chat_sessions' => [
            'comment' => 'AI 对话会话',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '会话用户微信 OpenID',
                'client_id' => '客户端会话 ID',
                'summary' => '会话摘要',
                'messages' => '会话消息列表',
                'context' => '会话上下文',
                'plan_id' => '关联用药计划 ID',
                'plan_ids' => '关联用药计划 ID 列表',
                'step' => '当前对话步骤',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_ai_parse_logs' => [
            'comment' => 'AI 解析调用日志',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '调用用户微信 OpenID',
                'feature' => 'AI 功能标识',
                'status' => '解析状态',
                'confidence' => '解析置信度',
                'error_code' => '错误码',
                'created_at' => '创建时间',
            ],
        ],
        'cn_ai_rate_limit' => [
            'comment' => 'AI 每日调用限流计数',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '调用用户微信 OpenID',
                'date' => '计数日期',
                'count' => '当日调用次数',
            ],
        ],
        'cn_events' => [
            'comment' => '用户行为埋点事件',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '事件用户微信 OpenID',
                'event_name' => '事件名称',
                'user_id' => '客户端用户标识',
                'session_id' => '客户端会话标识',
                'timestamp' => '事件发生时间戳',
                'app_version' => '客户端版本',
                'ref' => '来源引用标识',
                'ref_user_id' => '来源用户标识',
                'feature_module' => '功能模块',
                'source' => '事件来源',
                'quota_type' => '额度类型',
                'activation_type' => '激活类型',
                'reward_type' => '奖励类型',
                'step' => '流程步骤',
                'count' => '事件数量',
                'item_count' => '项目数量',
                'missing_field_count' => '缺失字段数量',
                'status' => '业务状态',
                'record_status' => '记录状态',
                'week_start' => '周起始日期',
                'share_target' => '分享目标',
                'current_streak' => '当前连续记录天数',
                'milestone' => '里程碑值',
                'last_streak' => '上次连续记录天数',
                'path' => '页面路径',
                'confidence_bucket' => '置信度分段',
                'error_code' => '错误码',
            ],
        ],
        'cn_invite_records' => [
            'comment' => '用户邀请记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '记录所属用户微信 OpenID',
                'inviter_openid' => '邀请人微信 OpenID',
                'invitee_openid' => '被邀请人微信 OpenID',
                'invite_token' => '邀请码令牌',
                'scene_path' => '邀请场景路径',
                'status' => '邀请状态',
                'activations' => '邀请激活明细',
                'created_at' => '创建时间',
                'archived_at' => '归档时间',
            ],
        ],
        'cn_user_entitlements' => [
            'comment' => '用户权益账户',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '权益用户微信 OpenID',
                'medicine_limit' => '药品数量上限',
                'plan_limit' => '用药计划数量上限',
                'family_member_limit' => '家庭成员数量上限',
                'ai_chat_monthly' => '每月 AI 对话额度',
                'ocr_monthly' => '每月 OCR 识别额度',
                'ai_voice_basic_monthly' => '每月基础 AI 语音额度',
                'medication_sheet_import_monthly' => '每月用药单导入额度',
                'advanced_monthly_report_enabled' => '是否启用高级月报',
                'early_bird' => '是否为早鸟用户',
                'early_bird_capacity_plus_50' => '是否享有早鸟容量加成 50%',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_entitlement_grants' => [
            'comment' => '用户权益发放记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '权益用户微信 OpenID',
                'grant_type' => '权益发放类型',
                'reward' => '发放的奖励内容',
                'source_id' => '权益来源业务 ID',
                'description' => '发放说明',
                'created_at' => '创建时间',
            ],
        ],
        'cn_quota_usage' => [
            'comment' => '用户额度使用统计',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '额度用户微信 OpenID',
                'quota_type' => '额度类型',
                'period' => '统计周期',
                'used' => '已使用额度',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_user_streaks' => [
            'comment' => '用户连续记录统计',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '统计用户微信 OpenID',
                'current_streak' => '当前连续记录天数',
                'longest_streak' => '最长连续记录天数',
                'last_record_date' => '最近记录日期',
                'valid_record_dates' => '有效记录日期列表',
                'milestones_reached' => '已达到的里程碑列表',
                'milestones_claimed' => '已领取的里程碑列表',
                'backfilled' => '是否执行过历史数据回填',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_weekly_reports' => [
            'comment' => '用户健康周报',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '周报用户微信 OpenID',
                'family_id' => '所属家庭 ID',
                'week_start' => '统计周开始时间',
                'week_end' => '统计周结束时间',
                'week_key' => '统计周唯一标识',
                'metrics' => '周报指标数据',
                'viewed' => '是否已查看',
                'viewed_at' => '首次查看时间',
                'shared_count' => '分享次数',
                'generated_at' => '周报生成时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_weekly_share_snapshots' => [
            'comment' => '健康周报分享快照',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'report_id' => '关联周报 ID',
                'family_id' => '所属家庭 ID',
                'week_start' => '统计周开始时间',
                'week_end' => '统计周结束时间',
                'week_key' => '统计周唯一标识',
                'metrics' => '分享时的周报指标快照',
                'view_count' => '分享页面查看次数',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
                'last_viewed_at' => '最近查看时间',
            ],
        ],
        'cn_documents' => [
            'comment' => '协议与说明文档',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'type' => '文档类型',
                'title' => '文档标题',
                'content' => '文档正文',
                'version' => '文档版本',
                'update_date' => '文档标注的更新日期',
                'is_active' => '是否启用',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_user_agreements' => [
            'comment' => '用户协议同意记录',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'openid' => '同意协议的用户微信 OpenID',
                'privacy_version' => '隐私政策版本',
                'agreement_version' => '用户协议版本',
                'agreed_at' => '同意时间',
            ],
        ],
        'cn_faq_categories' => [
            'comment' => '常见问题分类',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'business_id' => '原业务分类 ID',
                'title' => '分类标题',
                'icon' => '分类图标',
                'color' => '分类主题色',
                'order' => '显示顺序',
                'is_active' => '是否启用',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_faq_items' => [
            'comment' => '常见问题条目',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'business_id' => '原业务问题 ID',
                'category_id' => '所属业务分类 ID',
                'question' => '问题内容',
                'answer' => '答案内容',
                'order' => '显示顺序',
                'is_active' => '是否启用',
                'views' => '查看次数',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
        'cn_changelogs' => [
            'comment' => '产品更新日志',
            'columns' => [
                'id' => '原云数据库记录 ID',
                'version' => '发布版本号',
                'title' => '更新标题',
                'description' => '更新说明',
                'release_date' => '发布日期',
                'features' => '功能更新列表',
                'is_active' => '是否启用',
                'is_highlighted' => '是否重点展示',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ],
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const USER_COLUMNS = [
        'gender' => '用户性别',
        'tracking_enabled' => '是否允许行为数据追踪',
        'privacy_v1_1_seen' => '是否已查看 1.1 版隐私提示',
        'invite_token' => '用户专属邀请令牌',
        'theme_id' => '用户选择的主题 ID',
        'onboarding' => '新手引导状态',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->assertCompleteColumnComments();

        foreach (self::TABLES as $table => $definition) {
            DB::statement(sprintf(
                'COMMENT ON TABLE %s IS %s',
                $this->quoteIdentifier($table),
                $this->quoteLiteral($definition['comment']),
            ));

            foreach ($definition['columns'] as $column => $comment) {
                $this->commentOnColumn($table, $column, $comment);
            }
        }

        foreach (self::USER_COLUMNS as $column => $comment) {
            $this->commentOnColumn('users', $column, $comment);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::TABLES as $table => $definition) {
            foreach (array_keys($definition['columns']) as $column) {
                $this->commentOnColumn($table, $column, null);
            }

            DB::statement(sprintf(
                'COMMENT ON TABLE %s IS NULL',
                $this->quoteIdentifier($table),
            ));
        }

        foreach (array_keys(self::USER_COLUMNS) as $column) {
            $this->commentOnColumn('users', $column, null);
        }
    }

    private function assertCompleteColumnComments(): void
    {
        foreach (self::TABLES as $table => $definition) {
            $actualColumns = Schema::getColumnListing($table);
            $commentedColumns = array_keys($definition['columns']);
            $missingComments = array_diff($actualColumns, $commentedColumns);
            $unknownColumns = array_diff($commentedColumns, $actualColumns);

            if ($missingComments !== [] || $unknownColumns !== []) {
                throw new RuntimeException(sprintf(
                    'CareNote comment mapping mismatch for [%s]. Missing comments: [%s]. Unknown columns: [%s].',
                    $table,
                    implode(', ', $missingComments),
                    implode(', ', $unknownColumns),
                ));
            }
        }
    }

    private function commentOnColumn(string $table, string $column, ?string $comment): void
    {
        DB::statement(sprintf(
            'COMMENT ON COLUMN %s.%s IS %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($column),
            $comment === null ? 'NULL' : $this->quoteLiteral($comment),
        ));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function quoteLiteral(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
};
