# CareNote 云数据库迁移设计

## 范围

本次迁移覆盖 CareNote 小程序代码声明或实际读写的全部 28 个云数据库集合。
Laravel 自带表、管理端表、权限表、Token 表、队列表以及现有 `cn_ai_*`
管理表不属于旧库迁移范围。除 `users` 与 `user_identities` 外，旧集合对应的
业务表统一增加 `cn_` 前缀。

## 映射规则

| 云数据库字段 | PostgreSQL 字段 | 说明 |
|---|---|---|
| 集合名 | `cn_集合名` | `users`、`user_identities` 除外 |
| `_id` | `id` | `users` 生成新 ULID；其他集合保留原字符串值 |
| `_openid` | `openid` | 用户集合的 `_openid` 写入 `user_identities.provider_subject` |
| `users.nickname` | `users.display_name` | 复用 API 已有字段 |
| `users.avatar` | `users.avatar_url` | 复用 API 已有字段 |
| `faq_categories.id` | `cn_faq_categories.business_id` | `id` 已用于原 `_id` |
| `faq_items.id` | `cn_faq_items.business_id` | `id` 已用于原 `_id` |
| Date | `timestamp with time zone` | 统一保留时区 |
| Array / Object | `jsonb` | 不拆分、不新增业务字段 |
| number（库存、剂量） | `decimal(14,4)` | 避免浮点精度损失 |

## 集合清单

| 分组 | 集合 |
|---|---|
| 用户与家庭 | `users`、`cn_families`、`cn_family_members` |
| 药品与用药 | `cn_medicines`、`cn_medicine_versions`、`cn_medication_plans`、`cn_medication_records`、`cn_health_logs`、`cn_inventory_records` |
| 就诊与提醒 | `cn_visits`、`cn_follow_up_subscriptions`、`cn_alarm_setup_logs` |
| AI | `cn_chat_sessions`、`cn_ai_parse_logs`、`cn_ai_rate_limit` |
| 邀请与埋点 | `cn_events`、`cn_invite_records` |
| 权益与成长 | `cn_user_entitlements`、`cn_entitlement_grants`、`cn_quota_usage`、`cn_user_streaks` |
| 周报 | `cn_weekly_reports`、`cn_weekly_share_snapshots` |
| 内容 | `cn_documents`、`cn_user_agreements`、`cn_faq_categories`、`cn_faq_items`、`cn_changelogs` |

## 用户迁移

`users` 继续作为小程序业务用户表，`admin_users` 继续作为管理端用户表。

每个旧用户按以下顺序迁移：

1. 为旧 `users._id` 生成新的 `users.id` ULID。
2. `nickname`、`avatar` 分别写入 `display_name`、`avatar_url`。
3. 其余现有用户字段写入同语义字段。
4. 使用旧 `_openid` 创建 `user_identities` 记录：
   - `provider = wechat_mini_program`
   - `provider_subject = 旧 _openid`
   - `user_id = 新 users.id`

`cn_` 业务表通过 `openid` 字段保留原身份值，数据导入后可直接与
`user_identities.provider_subject` 对照。

## 数据导入顺序

1. `users`、`user_identities`
2. `cn_families`、`cn_family_members`
3. `cn_medicines`、`cn_medicine_versions`
4. `cn_visits`、`cn_medication_plans`、`cn_medication_records`
5. `cn_health_logs`、`cn_inventory_records`
6. 提醒、AI、权益、周报、邀请埋点和内容集合

## 导入前检查

- 从微信云开发控制台导出全部集合，而不是只导出代码中的类型定义。
- 保留 Date 的类型信息，避免先转成无时区字符串。
- 检查重复 `_id`、重复邀请码、重复权益账户和重复周报键。
- 检查引用不存在的 `family_id`、`member_id`、`medicine_id`、`plan_id`。
- 导入前备份 PostgreSQL；导入过程按集合记录成功、失败和跳过数量。
