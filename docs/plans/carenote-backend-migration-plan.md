# CareNote 业务、数据与资源迁移方案

> 目标仓库：`E:\code\CareNote\CareNoteAPI`  
> 来源仓库：`E:\code\CareNote\CareNote`  
> 生成日期：2026-07-29  
> 状态：规划完成，待关键技术选型确认后实施

## 1. 结论

推荐采用“Laravel 模块化单体 + 版本化 HTTP API + PostgreSQL + Cloudflare R2 + Redis 队列/定时任务”的目标架构，并按业务域逐步切换。

迁移不是简单复制 `cloudfunctions/`：当前 CareNote 同时依赖微信云函数、云数据库、云存储和微信开放能力，必须把数据归属、家庭权限、原子写入、定时任务和文件访问一起迁移。迁移期间坚持“一个业务域同一时刻只有一个写入源”，避免客户端、云数据库和 Laravel 三方双写造成数据分叉。

迁移完成后：

- `CareNote` 只保留 uni-app 页面、Pinia 状态、游客示例数据、API 客户端和必须随小程序打包的 UI 资源。
- `CareNoteAPI` 承担鉴权、业务规则、权限校验、数据持久化、动态文件、AI/微信第三方集成、定时任务和后台运维能力。
- 微信云开发进入只读观察期，核对无误后再停止云函数、云数据库和云存储。

## 2. 规划就绪度

**就绪度：91/100**

| 维度 | 得分 | 说明 |
|---|---:|---|
| 问题清晰度 | 27/30 | 目标是将 CareNote 的服务端职责迁至 CareNoteAPI |
| 功能范围 | 22/25 | 已包含业务代码、表、历史数据和动态资源 |
| 成功标准 | 18/20 | API 自动化、数据一致性、客户端回归三重验收 |
| 约束 | 15/15 | 明确要求分阶段兼容，并已确定 PostgreSQL 与 Cloudflare R2 |
| 优先级/交付 | 9/10 | 按依赖顺序分阶段上线 |

已确认：

- 生产数据库：PostgreSQL。
- 文件存储：Cloudflare R2。

仍需确认：

1. 每个业务域可接受的短暂停写窗口；若不能停写，需要额外建设变更日志/同步桥。
2. 微信云数据库和云存储的生产数据量、最大单表量、文件总量及导出权限。
3. R2 的账户、bucket 划分、公共资源自定义域名和客户端直传策略。

## 3. 现状盘点

### 3.1 CareNoteAPI

- Laravel 13、PHP 8.3、Inertia React。
- 当前主要是 Fortify 登录/注册、个人设置和 Dashboard 脚手架。
- 只有 `users`、`cache`、`jobs` 及双因素认证相关迁移。
- 尚无 `routes/api.php`、业务模型、业务 API、业务队列和业务定时任务。
- 默认数据库配置仍是 SQLite；实施时切换为 PostgreSQL，并保留独立测试数据库。

### 3.2 CareNote

- uni-app + Vue 3 + TypeScript，支持微信小程序/H5/APP。
- 后端全部依赖微信云开发。
- 已识别 28 个数据库集合、51 个云函数和 7 个定时任务。
- 客户端 service、页面、组件、hook 和 utils 中仍存在直接调用 `wx.cloud`/云数据库的代码，不能只替换一个公共入口完成迁移。
- 家庭数据权限依赖 `family_id`、`member_openids`、`linked_user_openid` 和 `_openid`，当前部分权限在客户端查询条件或云数据库规则中实现。
- 动态资源涉及药品图片、头像、健康记录、就诊资料、用药单、聊天图片、语音临时文件、二维码等。

### 3.3 静态资源边界

本地可见资源：

| 类型 | 位置 | 数量/体积 | 迁移结论 |
|---|---|---:|---|
| 小程序编译期 UI 资源 | `CareNote/src/static` | 9 个，约 7.6 KB | 保留在客户端；移走会增加启动依赖和离线失败风险 |
| 官网展示资源 | `CareNote/landing-web/public/assets` | 24 个，约 1.5 MB | 可迁到 API 公共存储/CDN，但与核心业务切换解耦 |
| 营销源文件 | `CareNote/docs/marketing` | 约 5.9 MB | 属于内容生产资料，不进入业务对象存储，除非后续建设内容管理 |
| 微信云存储动态文件 | 生产云环境 | 体积未知 | 必须迁移到对象存储并建立文件映射与校验 |

“静态资源全部迁到后台”在实施中应解释为：后台统一管理运行时动态文件和可远程发布的内容资源；小程序 tabbar、图标等编译期资源继续随客户端发布。

## 4. 目标架构

### 4.1 架构原则

1. **模块化单体优先**：在 Laravel 内按领域划分目录和服务，不在本次迁移中引入微服务。
2. **服务端拥有业务规则**：客户端不再决定 `family_id`、用户身份、权限、额度和库存变化。
3. **兼容优先**：第一阶段 API Resource 暂时兼容 `_id` 和现有字段格式，降低 CareNote 页面改造量。
4. **单写源**：每个领域切换后只允许写 Laravel；旧云端仅作短期只读回退。
5. **幂等和事务**：邀请、库存、权益、记录生成、奖励领取和通知发送必须具备唯一约束或幂等键。
6. **私有健康数据默认私有**：业务附件通过短期签名 URL 访问，不直接暴露永久公网地址。

### 4.2 后端模块

建议按以下领域组织：

- `Identity`：微信登录、用户身份、Token、注销。
- `Family`：家庭、成员、绑定、邀请、家庭权限。
- `Medication`：药品、版本、计划、服药记录、库存。
- `Health`：健康记录、就诊、复查订阅。
- `Content`：FAQ、文档、协议、更新日志。
- `Growth`：邀请归因、权益、额度、连续记录、周报。
- `Assistant`：聊天、OCR、语音、AI 解析。
- `Media`：上传、签名 URL、删除、迁移映射。
- `Notification`：微信订阅消息、提醒、失败重试。
- `Analytics`：事件埋点、限流与数据清理。

### 4.3 身份与权限

不建议把小程序用户与后台管理员混在同一认证模型中：

- 后台管理用户迁至 `admin_users`，继续使用 Fortify Session。
- 小程序业务用户使用 `users`。
- 新增 `user_identities`，保存 `provider=wechat_miniprogram`、`openid`、`unionid`、`appid` 等身份字段。
- 小程序调用 `/api/v1/auth/wechat/login`，Laravel 服务端用 `code` 换取微信身份并签发 API Token。
- 所有家庭资源通过 Policy/查询作用域校验当前用户是否属于家庭，不能信任客户端提交的 `family_id`。
- `member_openids` 数组改为关系表，避免权限判断依赖 JSON 数组。

### 4.4 ID 与时间

- 迁移数据保留云数据库 `_id`，初期可直接作为字符串主键，避免跨集合关系重写。
- 新数据统一使用 ULID 字符串，与旧字符串 ID 共存。
- API 过渡期同时返回 `id` 和 `_id`；CareNote 完成切换后删除 `_id` 兼容字段。
- 数据库存 UTC，API 返回 ISO 8601；业务日、提醒和周报明确使用 `Asia/Shanghai`。
- MongoDB Date、字符串日期和缺失日期在导入前必须分型清洗。

### 4.5 PostgreSQL 与 Cloudflare R2 基线

PostgreSQL：

- 嵌套且短期不稳定的字段使用 `jsonb`，只有实际查询的 JSON 路径才建立 GIN/表达式索引。
- 家庭成员、身份、邀请和附件关系必须正规化，不能用 JSON 数组替代关系表。
- 时间字段使用 `timestamptz`；业务唯一键使用唯一索引，含可空条件时使用部分唯一索引。
- 库存、权益、奖励领取使用数据库事务、行锁和幂等键。
- 云数据库字符串 `_id` 继续使用 `varchar` 保存，新记录使用 ULID 字符串。

Cloudflare R2：

- Laravel 通过 S3 兼容接口接入，endpoint 使用 `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`，region 使用 `auto`。
- API Token 只授予指定 bucket 的 Object Read & Write；迁移账号与应用运行账号分开。
- 私有健康资源保持私有，通过 S3 API 域名生成短期预签名 URL；R2 预签名 URL 不能使用自定义域名。
- 公共官网/分享资源使用独立公共 bucket 或严格隔离的公共 bucket，并绑定正式自定义域名；生产环境不依赖 `r2.dev`。
- 客户端直传时配置精确 CORS origin、method 和 header；不使用 `*` 放开生产写入。
- 临时 OCR/语音对象配置 lifecycle 自动过期，业务删除另走延迟清理队列。

## 5. 数据表迁移

### 5.1 身份、家庭与合规

| 云集合 | Laravel 表/模型 | 迁移策略 |
|---|---|---|
| `users` | `users`、`user_identities` | `_openid` 迁入身份表；业务资料留在用户表 |
| `families` | `families`、`family_user` | `member_openids` 拆为关系表；保留创建者审计字段 |
| `family_members` | `family_members` | `linked_user_openid` 转为可空 `user_id` |
| `documents` | `documents` | 文档版本和发布状态结构化 |
| `user_agreements` | `user_agreements` | 用户、文档版本建立外键和唯一约束 |

### 5.2 用药与健康主链路

| 云集合 | Laravel 表/模型 | 关键约束 |
|---|---|---|
| `medicines` | `medicines` | 家庭归属；库存更新使用事务和行锁/原子 SQL |
| `medicine_versions` | `medicine_versions` | 关联主药品；保留历史版本 |
| `medication_plans` | `medication_plans` | 家庭、成员、药品外键；计划状态索引 |
| `medication_records` | `medication_records` | `(plan_id, scheduled_time)` 唯一，防止重复生成 |
| `health_logs` | `health_logs` | 家庭和成员权限；结构化指标可使用 JSON |
| `inventory_records` | `inventory_records` | `(family_id, inventory_date)`、`medicine_id` 索引 |
| `visits` | `visits` | 就诊资料、化验指标等嵌套字段先使用 JSON，稳定后再拆表 |
| `follow_up_subscriptions` | `follow_up_subscriptions` | 状态、提醒日期、用户索引和发送幂等键 |
| `alarm_setup_logs` | `alarm_setup_logs` | 保留平台闹钟设置结果和错误审计 |

### 5.3 内容

| 云集合 | Laravel 表/模型 | 迁移策略 |
|---|---|---|
| `faq_categories` | `faq_categories` | 保留排序和启用状态 |
| `faq_items` | `faq_items` | 分类外键、排序和发布状态 |
| `changelogs` | `changelogs` | feature 数组可拆为 `changelog_features` |

### 5.4 AI 与会话

| 云集合 | Laravel 表/模型 | 迁移策略 |
|---|---|---|
| `chat_sessions` | `chat_sessions`、可选 `chat_messages` | 大型消息数组建议拆表；敏感内容设置保留周期 |
| `ai_parse_logs` | `ai_parse_logs` | 不写入原始敏感文本，保留模型、耗时、结果码 |
| `ai_rate_limit` | Redis 限流 + 可选审计表 | 实时计数迁 Redis，审计数据按需保留 |

### 5.5 增长、权益与分析

| 云集合 | Laravel 表/模型 | 关键约束 |
|---|---|---|
| `events` | `events` | 按时间索引；后续可分区/归档 |
| `invite_records` | `invite_records` | `(inviter_user_id, invitee_user_id)` 唯一 |
| `user_entitlements` | `user_entitlements` | 每用户唯一；额度变更必须事务化 |
| `entitlement_grants` | `entitlement_grants` | `source_id`/业务幂等键唯一 |
| `quota_usage` | `quota_usage` | `(user_id, quota_type, period)` 唯一 |
| `user_streaks` | `user_streaks` | 每用户唯一 |
| `weekly_reports` | `weekly_reports` | `(user_id, week_start)` 或既有业务键唯一 |
| `weekly_share_snapshots` | `weekly_share_snapshots` | 分享 token 唯一、过期索引、只保存脱敏内容 |

### 5.6 后端新增基础表

- `admin_users`：后台管理员，与业务用户隔离。
- `user_identities`：微信等外部身份。
- `family_user`：家庭成员用户关系。
- `media_assets`：对象 key、来源 fileID、哈希、大小、MIME、归属、可见性、迁移状态。
- `migration_batches`、`migration_failures`：记录导入批次、游标、错误和重试。
- `notification_deliveries`：消息发送幂等、结果、重试次数。
- `changelog_features`、`chat_messages`：是否拆分可在样本数据分析后最终决定。

## 6. 云函数迁移方式

不按 51 个云函数逐个照搬，按职责转换：

| 现有类型 | 目标实现 | 示例 |
|---|---|---|
| 鉴权/账户 | API Controller + Application Service | `login`、`deleteAccount` |
| 家庭命令 | API + Policy + 数据库事务 | 创建/加入/退出/绑定/改名 |
| 普通查询和 CRUD | REST API + Resource + Query Service | 药品、计划、记录、就诊、FAQ |
| 定时任务 | Laravel Scheduler 调度队列 Job | 每日记录、漏服、库存、过期、周报、复查 |
| 微信开放能力 | `WechatGateway` 适配器 | code2session、小程序码、订阅消息 |
| AI/OCR/语音 | Provider Gateway + Queue Job | DashScope、百度语音、微信 OCR |
| 奖励/额度 | 事务服务 + 唯一幂等键 | 邀请奖励、周报奖励、streak 奖励 |
| Seed/数据修复 | Seeder 或 Artisan Command | 药品、changelog、历史修复脚本 |
| 一次性旧迁移函数 | 导入完成后归档，不迁为线上 API | `migrate*`、`backfill*`、`fix*` |

现有枚举中引用了 `updateStock`、`generateReport`，但未发现对应云函数目录；迁移前应通过生产日志确认是否是遗留死配置。

### 6.1 定时任务映射

| 云任务 | 当前频率 | Laravel 目标 |
|---|---|---|
| `generateDailyRecords` | 每日 00:30 | Scheduler + 分家庭/计划队列，唯一键防重复 |
| `markMissedRecords` | 每日 01:00 | 批量 Job，记录影响行数 |
| `checkExpiredVersions` | 每日 09:00 | 扫描 Job + 通知队列 |
| `checkStockAlert` | 每日 09:00 | 扫描 Job + 通知队列 |
| `sendFollowUpReminder` | 每日 09:00 | 到期订阅队列 |
| `sendReminder` | 每小时 | 时间窗扫描 + 发送幂等 |
| `generateWeeklyReports` | 每周一 00:30 | 周报生成队列 |

服务端需配置 cron 每分钟执行 `schedule:run`，并使用持久化队列；生产环境不能使用同步队列。

### 6.2 首批 API 边界

以下是迁移期建议保持稳定的资源边界；具体字段以阶段 0 固化的 OpenAPI 为准：

| 路由组 | 主要能力 |
|---|---|
| `/api/v1/auth/wechat/*` | 微信登录、重新签发 Token、退出 |
| `/api/v1/me/*` | 当前用户、资料、协议、注销 |
| `/api/v1/families/*` | 家庭列表、创建、改名、加入、退出、邀请预览 |
| `/api/v1/families/{family}/members/*` | 成员 CRUD、档案绑定 |
| `/api/v1/medicines/*` | 药品、版本、图片、库存状态 |
| `/api/v1/plans/*` | 用药计划、停用、关联就诊 |
| `/api/v1/records/*` | 日历记录、打卡、补服、延期、统计 |
| `/api/v1/inventory/*` | 盘点、补充、流水 |
| `/api/v1/health-logs/*` | 健康记录 |
| `/api/v1/visits/*` | 就诊、化验数据、附件、复查 |
| `/api/v1/media/*` | 上传凭证/直传、签名访问、删除 |
| `/api/v1/assistant/*` | OCR、语音、计划解析、聊天、用药单 |
| `/api/v1/entitlements/*` | 当前权益、额度、发放记录 |
| `/api/v1/referrals/*` | 邀请码、归因、奖励记录 |
| `/api/v1/reports/*` | 周报、分享快照、奖励领取 |
| `/api/v1/streaks/*` | 连续记录摘要和奖励 |
| `/api/v1/content/*` | FAQ、文档、协议、更新日志 |
| `/api/v1/events` | 批量埋点上报 |

列表接口应统一分页、排序、时间范围和增量同步参数；命令接口使用幂等键，不能把任意云数据库 `where` 条件开放给客户端。

### 6.3 51 个云函数处置清单

| 领域 | 云函数 | 目标去向 |
|---|---|---|
| 身份与账户 | `login`、`deleteAccount`、`getCalendarPathSignature` | Auth API、Account Service、WeChatGateway；核实签名函数是否仍在用 |
| 家庭与成员 | `createFamily`、`joinFamily`、`joinFamilyV2`、`leaveFamily`、`updateFamilyName`、`getFamilyMembers`、`getUsersByOpenids`、`checkFamilyPermission`、`bindMemberToUser`、`generateInviteCode`、`previewInvite` | Family API + Policy + 邀请事务；V1 暂保兼容，稳定后删除 |
| 用药与库存 | `generateDailyRecords`、`markMissedRecords`、`sendReminder`、`checkStockAlert`、`checkExpiredVersions` | Medication/Notification Job |
| 药品维护 | `seedMedicines`、`migrateMedicineVersions`、`cleanOrphanVersions`、`fixStockType` | Seeder/Artisan Command；历史迁移完成后不暴露 API |
| 就诊与复查 | `createFollowUpSubscription`、`sendFollowUpReminder` | Visit Service + Notification Job |
| AI 与媒体 | `chatAssistant`、`ocrMedicine`、`parseMedicationSheet`、`parseVoiceToPlan`、`recognizeVoice`、`speakMedicine` | Assistant API + Provider Gateway + Queue |
| 邀请与埋点 | `ensureInviteToken`、`generateShareQr`、`recordShareAttribution`、`getReferralRecords`、`track`、`cleanOldEvents` | Referral/Analytics API、微信小程序码 Gateway、归档 Command |
| 权益与额度 | `getUserEntitlements`、`getEntitlementGrants`、`checkQuota`、`grantEntitlementReward` | Entitlement Query/Transaction Service |
| 周报 | `generateWeeklyReports`、`claimWeeklyReward`、`getWeeklyShareSnapshot` | Report Job/API，领取操作使用幂等事务 |
| 连续记录 | `getStreakSummary`、`claimStreakReward`、`updateStreaks`、`backfillStreaks` | Streak Query/Transaction Service；backfill 改 Command |
| 内容 | `seedChangelogs` | 后台内容管理 + Seeder/Command |
| 历史数据修复 | `migrateExistingUsers`、`migrateInviteTokens` | 仅作迁移参考，Laravel 侧使用幂等 Command 重写 |

以上清单覆盖当前 `cloudfunctions/` 的 51 个目录。阶段 0 仍需结合生产调用日志确认“在用/可废弃”，防止把未使用的历史实现带入新后台。

## 7. 动态资源迁移

### 7.1 目标分类

- **私有健康资源**：药品照片、健康记录附件、就诊/化验资料、用药单、聊天图片。
- **临时资源**：语音识别录音、OCR 中间图片，设置自动过期和清理。
- **公共资源**：官网图片、公开分享图、小程序码，可经 CDN 访问。
- **客户端资源**：tabbar 和内置图标继续随小程序打包。

### 7.2 迁移流程

1. 导出所有含 `fileID`、`photo_urls`、头像和附件字段的记录。
2. 建立 `source_file_id -> target_object_key` 清单。
3. 从微信云存储下载并流式上传对象存储，禁止整批驻留本机。
4. 记录 SHA-256、字节数、MIME 和迁移状态。
5. 回写 `media_assets` 关系，不直接把永久公网 URL 写入业务表。
6. 对私有资源生成短期签名 URL；删除业务记录时进入延迟清理队列。
7. 抽检预览，并对全量对象执行数量、大小和哈希核对。
8. 经过观察期后再删除微信云存储源文件。

## 8. CareNote 客户端改造

### 8.1 先建立兼容层

新增统一 HTTP 客户端，负责：

- `baseURL`、Token、超时、重试和错误码映射。
- 统一响应结构、分页结构和日期反序列化。
- 401 重新登录和 Token 刷新/重取。
- 上传文件、获取签名 URL。
- 按业务域配置数据源：`cloud` 或 `api`。

现有 `src/services/*` 的公开方法尽量保持不变，先替换内部实现，避免页面同时大改。

### 8.2 清除直连

需要专项清理：

- 页面和组件中的 `wx.cloud.database()`、`.collection()`。
- 页面和组件中的 `wx.cloud.callFunction()`。
- `wx.cloud.uploadFile/getTempFileURL/deleteFile/downloadFile`。
- 依赖客户端注入 `family_id` 的 `BaseService` 机制。

迁移完成后加入 lint/CI 禁止规则：业务代码不得直接引用 `wx.cloud`，只允许尚未下线的迁移适配器目录临时使用。

### 8.3 保留能力

- 游客模式和 `src/data/example-*` 示例数据继续留在客户端，不访问后端。
- UI、路由、Pinia 和编译期资源不迁移。
- 微信端原生能力仍可由客户端发起授权，但授权结果和业务写入由 API 校验。

## 9. 历史数据迁移策略

### 9.1 导出与暂存

1. 获取每个集合的文档数、索引、权限规则、最大文档、日期字段类型和最近写入时间。
2. 全量导出为原始 JSON/EJSON，保留 `_id`、Date、数组和嵌套对象。
3. 原始导出只读归档并加密保存，不直接作为应用数据库。
4. 导入 staging 表或按批次读取文件，再转换到正式表。

### 9.2 转换顺序

按外键依赖导入：

1. 用户、身份。
2. 家庭、家庭用户、家庭成员。
3. 药品、药品版本。
4. 计划、记录、库存、健康记录、就诊、复查订阅。
5. 内容、协议。
6. 权益、邀请、事件、周报、streak、AI/会话。
7. 动态文件和业务记录关联。

### 9.3 分阶段切换

每个业务域执行相同流程：

1. 全量预导入。
2. 运行 Laravel 只读影子查询，与云端响应做契约对比。
3. 执行增量导入；增量依据 `updated_at`，同时对无时间字段和删除记录做集合差异核对。
4. 在短暂停写窗口执行最终增量和删除差异处理。
5. 运行数据核对。
6. 将该业务域 feature flag 从 `cloud` 切为 `api`。
7. 观察错误率和业务指标，确认后才迁移下一域。

若实测数据规模导致最终同步超过可接受停写窗口，则在 CareNote 云端先增加变更日志/outbox；不建议让客户端长期同时写两个数据源。

### 9.4 数据核对

每个集合/表至少核对：

- 总数、分家庭数量、分用户数量。
- 主键唯一、业务唯一键重复。
- 外键缺失、孤儿记录、空 `family_id`。
- 日期类型、时区和排序结果。
- 药品当前库存与库存流水聚合。
- 计划数、每日服药记录数、状态分布。
- 权益余额与 grant/usage 汇总。
- 文件数量、总字节数和哈希。
- 删除账号后数据和文件是否完整清理。

导入命令必须支持 `--dry-run`、批次恢复和幂等重跑。

## 10. 分阶段实施计划

### 阶段 0：基线、选型和契约冻结

**动作**

- 确认 PostgreSQL 实例参数、R2 bucket/域名、Redis 队列和停写预算。
- 导出云数据库索引、权限规则、集合统计和云存储清单。
- 固化现有 service/云函数请求响应样本与错误码。
- 标记在用、废弃、一次性和仅管理员使用的云函数。
- 建立数据字典、字段类型冲突表和 API OpenAPI 基线。

**交付物**

- 数据字典与 ER 图。
- 云函数处置清单。
- API 契约基线。
- 数据/文件规模报告和迁移时间估算。

**放行条件**

- 28 个集合均有归属和处理策略。
- 51 个云函数均有“迁移/合并/改命令/废弃”结论。
- 生产基础设施选型得到确认。

### 阶段 1：Laravel 平台基础

**动作**

- 增加版本化 API、统一响应、异常和错误码。
- 建立业务用户、管理员和微信身份隔离。
- 实现 Token 鉴权、家庭 Policy、审计日志、限流。
- 配置 PostgreSQL、Redis、队列、Scheduler 和 Cloudflare R2。
- 建立 CI、OpenAPI 校验和 API 测试基座。

**交付物**

- `/api/v1` 基础框架。
- 微信登录闭环。
- Policy/Scope 权限框架。
- Media、WeChat、AI Provider 接口。

**放行条件**

- 跨家庭访问自动化测试全部拒绝。
- Token、注销、限流和异常响应通过测试。

### 阶段 2：身份、家庭和基础文件

**动作**

- 迁移用户、家庭、成员、邀请与绑定。
- 迁移头像并接入私有/公共文件策略。
- CareNote 的登录和家庭 service 切换到 API。
- 保留 V1/V2 加入家庭兼容行为。

**放行条件**

- 老用户登录后身份不变、家庭数量一致、成员绑定一致。
- 创建/加入/退出/改名/绑定和注销回归通过。

### 阶段 3：药品、计划、记录和库存

**动作**

- 迁移核心九张用药/健康表中的药品、版本、计划、记录和库存。
- 将库存、补服、计划变更、删除级联改为服务端事务。
- 先运行只读影子 API，再按业务域切写。
- 清除相关页面对云数据库和云存储的直接访问。

**放行条件**

- 库存与流水可对账。
- `(plan_id, scheduled_time)` 无重复。
- 添加药品、计划、打卡、补服、盘点和删除回归通过。

### 阶段 4：健康记录、就诊和复查

**动作**

- 迁移健康记录、就诊、附件、复查订阅。
- 接入签名 URL 和文件清理。
- 迁移就诊生成计划及关联关系。

**放行条件**

- 就诊资料、化验数据和附件完整。
- 家庭成员隔离、编辑和删除链路通过。

### 阶段 5：定时任务、消息与 AI

**动作**

- 迁移 7 个定时任务到 Scheduler/Queue。
- 接入微信订阅消息、小程序码和 code2session。
- 迁移 OCR、语音识别/TTS、用药单解析、聊天助手。
- 增加供应商超时、熔断、重试、额度扣减幂等和成本日志。

**放行条件**

- 定时任务可重复执行且不产生重复记录/通知。
- AI 失败不错误扣减额度，临时文件按期清理。
- 微信订阅消息在体验环境完成实机验证。

### 阶段 6：内容、增长、权益和报告

**动作**

- 迁移 FAQ、协议、更新日志。
- 迁移事件、邀请归因、权益、额度、streak、周报和分享快照。
- 为后台增加最小运维页面：用户/家庭检索、任务状态、迁移失败、通知失败、内容管理。

**放行条件**

- 权益账、邀请奖励和额度账可对账。
- 重复请求不会重复发奖。
- 分享快照不包含未授权健康明细。

### 阶段 7：最终切换和云开发退役

**动作**

- 完成所有最终增量和文件校验。
- CareNote 删除业务代码对 `wx.cloud` 的依赖。
- 云函数、云数据库、云存储进入只读观察期。
- 完成生产回归、备份恢复演练和回滚演练。
- 观察期结束后分批停用云任务和云函数，最后归档源数据。

**放行条件**

- 连续观察期内无数据差异和高优先级故障。
- 已验证备份恢复。
- 经负责人确认后才删除云端资源。

## 11. 回滚方案

### 11.1 阶段内回滚

- feature flag 按领域切回 `cloud`。
- 新 API 保持数据库数据，不立即删除，便于分析和重放。
- 在回滚窗口内，如果 Laravel 已产生新写入，必须先导出并转换回云端，或人工处理差异后才能重新开放旧端写入。

### 11.2 防止不可回滚

- 不做长期双写。
- 不在切换当天删除云函数、集合或文件。
- 所有迁移批次记录源游标、目标范围和校验结果。
- 数据库迁移优先可向后兼容的新增字段/表；破坏性结构变更放在观察期后。
- 文件迁移采用复制而非移动，源文件观察期后再删。

## 12. 测试与验收

### 12.1 后端自动化

- 每个 API 的正常、参数错误、未登录、跨家庭越权测试。
- 邀请、库存、权益、奖励、记录生成的并发和幂等测试。
- Scheduler 与 Job 的重复执行、失败重试测试。
- 微信、AI、对象存储使用 Fake/Contract Test。
- 账号删除的数据和文件级联测试。

### 12.2 迁移自动化

- 小样本、脏数据、重复数据、缺少 `family_id`、混合日期类型测试。
- 全量导入和增量导入可幂等重跑。
- 表计数、业务聚合、外键、文件哈希自动生成差异报告。
- 随机抽样对比云端文档、API Resource 和客户端显示。

### 12.3 CareNote 回归

- 微信登录、游客模式、家庭切换和邀请。
- 药品、计划、打卡、补服、库存、健康记录、就诊。
- OCR、语音、AI 聊天、用药单导入。
- 提醒、周报、streak、权益和分享。
- `pnpm type-check`、相关 lint、`pnpm build:mp`。

### 12.4 完成定义

- [ ] 28 个集合全部迁移或有经确认的废弃记录。
- [ ] 51 个云函数全部迁移、合并、命令化或废弃。
- [ ] 7 个定时任务均由 Laravel Scheduler/Queue 接管。
- [ ] 动态文件数量、总字节和哈希一致。
- [ ] 无跨家庭越权。
- [ ] 核心业务聚合数据一致。
- [ ] CareNote 业务代码不再直接调用 `wx.cloud`。
- [ ] API 自动化、迁移核对和小程序回归全部通过。
- [ ] 完成回滚和备份恢复演练。
- [ ] 观察期结束并人工确认后才退役微信云开发。

## 13. 风险管理

| 风险 | 影响 | 可能性 | 缓解措施 |
|---|---|---|---|
| MongoDB 动态字段映射到关系库时丢字段 | 高 | 高 | 原始 JSON 归档、字段画像、staging 导入、未知字段报告 |
| `_openid`/家庭权限迁移错误导致越权 | 极高 | 中 | 身份表、关系表、Policy、跨家庭自动化测试 |
| 增量期间删除记录无法靠 `updated_at` 捕获 | 高 | 高 | 短暂停写最终 diff；必要时先上 outbox |
| 库存、权益和奖励并发产生重复/负数 | 高 | 中 | 事务、行锁、唯一键、幂等键 |
| 时间与时区变化导致漏提醒/错周报 | 高 | 中 | UTC 存储、上海时区业务日、边界测试 |
| 云文件链接失效或漏迁 | 高 | 中 | fileID 清单、复制、哈希、签名 URL、延迟删源 |
| 微信开放能力无法直接从普通服务器复刻 | 高 | 中 | 独立 WeChatGateway、体验环境实机验证、保留短期云端回退 |
| 51 个函数中有重复/死代码 | 中 | 高 | 日志和调用清单确认，不机械移植 |
| API 改造范围过大导致客户端长期双栈 | 中 | 高 | service 方法兼容、按域 feature flag、设置云端退役截止条件 |
| 健康数据日志或 AI 请求泄露 | 极高 | 中 | 脱敏日志、私有存储、最小化传输、密钥集中管理 |

## 14. 预计文件变更

### CareNoteAPI

新增/修改的关键区域：

1. `routes/api.php`、`bootstrap/app.php`
   - 注册版本化 API、鉴权、限流和异常处理。
2. `app/Models`、`database/migrations`
   - 建立业务模型、关系、唯一键和索引。
3. `app/Http/Controllers/Api/V1`、`app/Http/Requests`、`app/Http/Resources`
   - API 边界、校验和兼容响应。
4. `app/Domain` 或 `app/Services`、`app/Policies`
   - 领域事务和家庭权限。
5. `app/Jobs`、`app/Console/Commands`、`routes/console.php`
   - 队列、定时任务和数据迁移命令。
6. `config/filesystems.php`、新增微信/AI/通知配置
   - 对象存储和第三方 Provider。
7. `tests/Feature/Api`、`tests/Feature/Migration`、`tests/Unit`
   - API、权限、任务、迁移和对账测试。
8. `resources/js`
   - 最小后台运维和内容管理页面。

### CareNote

关键修改区域：

1. `src/services`、新增 HTTP 客户端和按域适配器。
2. `src/cloud`，最终删除业务云开发初始化和 CRUD 封装。
3. 直接使用 `wx.cloud` 的页面、组件、hook 和 utils。
4. `src/types`，改为 API DTO 并在过渡期兼容 `_id`。
5. 环境配置和 CI，增加 API 地址、feature flag 与禁用直连检查。

## 15. 实施前必须确认

以下内容确认后才能进入业务编码：

1. **PostgreSQL 环境**：版本、连接信息、备份策略、连接池方案和独立测试库。
2. **Cloudflare R2**：账户 ID、bucket 划分、自定义域名、CORS、生命周期和密钥交付方式。
3. **停写预算**：是否允许每个业务域短暂停写；若完全不允许，先设计 outbox 同步桥。
4. **后台范围**：本方案只包含最小运维后台，不默认建设完整运营平台。
5. **数据权限**：需要微信云环境的集合导出、索引、权限规则、云存储清单和生产日志读取权限。
6. **观察期**：建议至少覆盖一个完整的每日任务和每周任务周期，再退役云端。

## 16. 方案审查

项目未配置 `all-plan` 所需的外部 `inspiration`/`reviewer` 角色，因此未执行外部协作者评分。已进行本地一致性审查：

| 维度 | 自审分数 |
|---|---:|
| 清晰度 | 9/10 |
| 完整性 | 9/10 |
| 可行性 | 8/10 |
| 风险覆盖 | 9/10 |
| 需求对齐 | 9/10 |
| **综合** | **8.8/10** |

扣分项主要来自生产数据量、R2 bucket/域名参数和可接受停写窗口尚未确认；这些信息会直接影响工期、同步桥是否必要以及最终回滚成本。
