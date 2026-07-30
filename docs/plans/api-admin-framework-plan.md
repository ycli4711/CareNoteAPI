# CareNote API 与管理端框架方案

> 目标：为微信小程序建立稳定的 API v1，同时保留未来 iOS/Android App 扩展能力，并用现有 Laravel + Inertia React 搭建独立管理端骨架。  
> 范围：第一阶段仅搭框架，不迁移药品、计划、家庭等业务模块。  
> 生成日期：2026-07-30  
> 状态：待确认后编码

## 1. 结论

推荐采用单仓库、三条明确边界：

1. **客户端 API**：`/api/v1/*`，面向小程序，未来 App 复用相同资源和契约。
2. **管理端 Web**：`/admin/*`，Laravel Session + Fortify + Inertia React。
3. **未来管理 API**：预留 `/api/admin/v1/*`，本阶段不创建，避免无实际消费者的接口。

管理员与业务用户完全隔离：

- `AdminUser` / `admin_users` / `admin` Session Guard：仅用于管理后台。
- `User` / `users` / Sanctum Token：仅用于小程序和未来 App。
- 管理员权限使用 `spatie/laravel-permission` v7，角色和权限固定使用 `admin` guard。
- 客户端 API 额外校验认证主体必须是 `User`，防止管理端 Session 被 Sanctum 的混合认证机制带入客户端 API。

## 2. 需求就绪度

**Readiness Score：92/100**

| 维度 | 得分 | 结论 |
|---|---:|---|
| 问题清晰度 | 27/30 | 新建可扩展的 API 与管理端框架 |
| 功能范围 | 23/25 | 第一阶段明确只做骨架 |
| 成功标准 | 18/20 | 自动化、权限、页面 smoke、构建检查 |
| 约束 | 15/15 | PostgreSQL、R2、身份隔离和未来 App 已明确 |
| 优先级 | 9/10 | 先骨架，后迁业务 |

剩余假设：

- 当前项目仍是可重建的开发期骨架，现有 `users` 表没有必须保留的生产管理员数据。
- 第一阶段不实现微信 code2session 登录，仅建立业务用户、外部身份和 Sanctum Token 基础。
- 管理端暂不开放自助注册，首位管理员通过交互式 Artisan Command 创建。

## 3. 现状与问题

### 3.1 当前可复用部分

- Laravel 13、Fortify、Inertia React、Tailwind、Wayfinder 已可运行。
- 登录、密码重置、邮箱验证、双因素认证和设置页面已有测试。
- Inertia 侧栏、布局、组件库可继续作为管理端基础。
- PostgreSQL 与 Cloudflare R2 已确定为目标基础设施。

### 3.2 当前必须纠正

- `App\Models\User` 和 `users` 表当前实际承担管理员身份，与后续业务用户冲突。
- Fortify 使用默认 `web/users` provider，没有管理员专用 Guard。
- 没有 `routes/api.php` 和 API 版本边界。
- 没有 Sanctum、Token 能力和客户端身份约束。
- 没有 RBAC。
- Dashboard 和 Logo 仍是 “Summer Closet” 电商示例。
- 管理路由没有 `/admin` 前缀，未来公共页面和 API 运维页面容易混杂。
- 没有 OpenAPI 基线和 `composer docs:api:check`。

## 4. 目标目录与边界

```text
app/
├── Actions/Fortify/                 # 管理员 Fortify 动作
├── Console/Commands/                # admin:create 等运维命令
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                   # 只返回 Inertia/Redirect
│   │   └── Api/V1/                  # 只返回 JSON/Resource
│   ├── Middleware/
│   │   ├── EnsureAppUser.php
│   │   └── AssignRequestId.php
│   ├── Requests/
│   │   ├── Admin/
│   │   └── Api/V1/
│   └── Resources/Api/V1/
├── Models/
│   ├── AdminUser.php
│   ├── User.php
│   └── UserIdentity.php
├── Policies/
└── Support/Api/
    ├── ApiResponse.php
    └── ApiErrorCode.php

routes/
├── web.php                           # 根入口
├── admin.php                         # Inertia 管理端
├── admin-settings.php
├── api.php                           # /api/v1
└── console.php

resources/js/
├── layouts/admin/
├── pages/admin/
├── components/admin/
└── types/admin-auth.ts

tests/Feature/
├── Admin/
├── Api/V1/
└── Architecture/
```

不为尚未实现的业务域创建空目录和空类；药品、家庭等模块在正式迁移时按领域落位。

## 5. 认证架构

### 5.1 管理员

| 项目 | 设计 |
|---|---|
| 模型 | `App\Models\AdminUser` |
| 表 | `admin_users` |
| Guard | `admin`，driver=`session` |
| Provider | `admins` |
| Password Broker | `admins` |
| 登录入口 | `/admin/login` |
| 登录后 | `/admin/dashboard` |
| 注册 | 禁用 |
| 保留能力 | 密码重置、邮箱验证、2FA、密码确认 |

Fortify 配置改为：

- `guard=admin`
- `passwords=admins`
- `prefix=admin`
- `home=/admin/dashboard`
- 移除 `Features::registration()`

现有 Profile、Security、2FA、密码重置动作、Factory 和测试全部改为 `AdminUser` 语义。

### 5.2 小程序和未来 App 用户

| 项目 | 设计 |
|---|---|
| 模型 | `App\Models\User` |
| 表 | `users` |
| API 认证 | Laravel Sanctum Bearer Token |
| 登录来源 | `user_identities`；第一阶段仅建结构 |
| API 中间件 | `auth:sanctum` + `app-user` |
| Token 能力 | `app:*`、后续按敏感能力细分 |

`users` 第一阶段保留最小字段：

- 字符串主键，可容纳未来云数据库旧 `_id` 与新 ULID。
- `display_name`
- `avatar_url`
- `status`
- `last_active_at`
- timestamps

`user_identities` 使用通用外部身份结构：

- `user_id`
- `provider`：初期为 `wechat_miniprogram`
- `provider_subject`：openid 等 provider 内唯一标识
- `union_id`：可空
- `metadata`：PostgreSQL `jsonb`
- `(provider, provider_subject)` 唯一

第一阶段不提供正式微信登录接口；测试通过 Factory 创建用户并签发 Sanctum Token，验证认证链路。

### 5.3 防止身份串线

- `AdminUser` 不使用 `HasApiTokens`。
- `User` 不进入 Fortify provider，也不具备后台密码字段。
- `/admin/*` 显式使用 `auth:admin`。
- `/api/v1/*` 使用 `auth:sanctum` 后再由 `EnsureAppUser` 校验 `$request->user()` 必须是 `User`。
- RBAC 角色固定 `guard_name=admin`，业务用户不能分配管理角色。
- Feature Test 必须覆盖“管理员不能访问客户端 API”和“业务用户不能访问管理端”。

## 6. RBAC

采用当前 Composer 为 Laravel 13 解析的 `spatie/laravel-permission:^8.3`。

### 6.1 第一阶段范围

- `AdminUser` 使用 `HasRoles`。
- 固定 `$guard_name='admin'`。
- 注册 `role`、`permission`、`role_or_permission` 中间件别名。
- 初始角色：
  - `super-admin`
  - `administrator`
- 初始权限：
  - `admin.access`
  - `admin.dashboard.view`
  - `admin.accounts.manage`
  - `admin.roles.manage`
  - `system.health.view`

`super-admin` 通过 `Gate::before` 放行；普通管理员必须显式拥有权限。

### 6.2 管理员创建

新增交互式命令：

```powershell
php artisan admin:create
```

命令要求输入姓名、邮箱、密码和角色：

- 不在 Seeder、`.env.example` 或 Git 中保存默认密码。
- 邮箱重复时拒绝。
- 密码走现有强度规则。
- 默认可创建为 `super-admin`，但必须显式确认。

第一阶段不做管理员和角色的 CRUD 页面，只建立模型、权限、命令和测试。

## 7. API v1

### 7.1 路由

`bootstrap/app.php` 注册 `routes/api.php`。

```text
GET  /api/v1/ping       公共连通性和 API 版本，不访问数据库
GET  /api/v1/me         Sanctum + app-user，返回当前业务用户
```

本阶段不实现：

- 微信登录
- Token 刷新
- 家庭、药品、计划等业务接口
- 管理 API

### 7.2 响应契约

成功：

```json
{
  "data": {},
  "meta": {
    "request_id": "..."
  }
}
```

失败：

```json
{
  "message": "请求处理失败",
  "code": "COMMON.INTERNAL_ERROR",
  "errors": {},
  "meta": {
    "request_id": "..."
  }
}
```

原则：

- HTTP 状态码表达传输结果，稳定业务错误码表达客户端行为。
- `ApiResource` 保留 Laravel 的 `data` 包装。
- 校验错误、未认证、无权限、限流和 404 在 `api/*` 下统一为 JSON。
- 管理端 Web 继续使用 Inertia 错误处理，不被 API 异常格式影响。

### 7.3 Request ID

`AssignRequestId`：

- 接受格式合法的 `X-Request-Id`，否则生成 UUID。
- 写入 response header。
- 注入 API `meta.request_id`。
- 加入日志 context，便于小程序报错与后台日志关联。

### 7.4 客户端兼容

- API 不出现 `mini-program` 专属路径，未来 App 直接复用 `/api/v1`。
- 可接受 `X-Client-Platform`、`X-Client-Version`、`X-Device-Id`，但第一阶段只记录，不作为安全凭证。
- breaking change 才升级 `/api/v2`；字段扩展和新增接口留在 v1。
- 小程序域名白名单、HTTPS 和发布环境地址属于部署配置，不写死在业务代码。

## 8. 管理端 Web 骨架

### 8.1 路由

```text
/                         -> /admin
/admin                    -> /admin/dashboard 或 /admin/login
/admin/login              Fortify
/admin/dashboard          admin + verified + permission
/admin/settings/*         admin
```

### 8.2 页面和布局

- 将现有 App layout 重命名/收敛为 Admin layout。
- Logo 和文案改为 CareNote 管理后台。
- 删除订单、商品、GMV 等电商占位数据。
- Dashboard 第一阶段仅展示框架状态：
  - API v1 已启用
  - PostgreSQL/R2/Queue 的“未配置/已配置”状态，不展示密钥和连接串
  - 当前管理员角色
  - 后续模块占位说明
- 侧栏采用配置化导航，每项可声明 permission。
- 无权限时返回 403，不以“隐藏菜单”代替服务端权限。

不在本阶段建设用户、家庭、药品、内容等管理页面。

## 9. OpenAPI 与质量门

### 9.1 OpenAPI

- 新增 `docs/api/openapi.yaml`，只描述 `/api/v1/ping` 和 `/api/v1/me`。
- 引入 OpenAPI lint 工具。
- 增加 npm script：`docs:api:check`。
- 增加 Composer wrapper：`composer docs:api:check`。
- 后续 API Controller、Request、Resource 或字段变更必须同步更新文档。

### 9.2 自动化测试

必须覆盖：

1. `AdminUser` 可通过 `/admin/login` 登录并访问 Dashboard。
2. 业务 `User` 不能登录管理端。
3. 未认证、无权限管理员访问管理页分别得到跳转/403。
4. `super-admin` 和普通权限角色行为正确。
5. Sanctum Token 用户可以访问 `/api/v1/me`。
6. 管理员 Session 或错误 Token 不能访问 `/api/v1/me`。
7. API 401/403/422/404 响应符合统一契约。
8. Response 带 `X-Request-Id`，JSON meta 与 header 一致。
9. Inertia Dashboard smoke test。
10. 数据库迁移可在 PostgreSQL 和测试 SQLite 上执行。

### 9.3 验证命令

```powershell
vendor\bin\pint 本次修改的PHP文件
php artisan test tests\Feature\Admin tests\Feature\Api
composer docs:api:check
npm run lint:check
npm run types:check
npm run build
composer test
```

最后一项在相关测试通过后执行，用于确认原 Fortify/设置测试没有因模型分离而回归。

## 10. 实施步骤

### Step 1：依赖与路由边界

**动作**

- 安装 Sanctum。
- 安装 `spatie/laravel-permission:^8.3`。
- 注册 `routes/api.php` 和 `routes/admin.php`。
- 建立 `/api/v1`、`/admin` 路由命名规范。

**交付**

- API、管理端和未来管理 API 的明确命名空间。

### Step 2：拆分管理员与业务用户

**动作**

- 将现有 `User`、Factory、Fortify 动作、测试迁为 `AdminUser`。
- 基线表改为 `admin_users`、管理员密码重置表和管理员 Session。
- 新增最小业务 `users` 和 `user_identities`。
- 新增 Sanctum Token 表。

**交付**

- 两套完全独立的身份模型和数据表。

**前置条件**

- 必须确认数据库可重建；如已有不可丢数据，改为增量迁移而不是重写基线。

### Step 3：管理端 Fortify 与 RBAC

**动作**

- 配置 `admin` Guard/provider/password broker。
- Fortify 改用管理员 Guard 和 `/admin` 前缀。
- 禁用注册。
- 配置 Spatie admin guard、Seeder 和 `admin:create`。
- 更新设置、2FA、密码重置及所有认证测试。

**交付**

- 安全可用的管理端登录与权限基础。

### Step 4：API v1 基础

**动作**

- 建立 Sanctum token-only 使用约束。
- 增加 `EnsureAppUser`、Request ID、API Response 和错误码。
- 实现 `/api/v1/ping`、`/api/v1/me`。
- 增加限流和 JSON 异常渲染。

**交付**

- 可被小程序/App 复用的 API 基线。

### Step 5：管理页面骨架

**动作**

- 管理路由和页面迁入 `/admin`。
- 替换 Summer Closet 品牌与占位数据。
- 建立权限感知的侧栏配置。
- 增加 Dashboard 状态卡和 403 行为。

**交付**

- CareNote 管理端基本页面与导航。

### Step 6：文档、测试和质量门

**动作**

- 添加 OpenAPI。
- 添加认证隔离、RBAC、API 契约和页面 smoke 测试。
- 添加文档校验脚本。
- 执行 PHP 和前端完整验证。

**交付**

- 可持续扩展业务模块的质量基线。

## 11. 预计文件变更

### 新增

- `app/Models/AdminUser.php`
- `app/Models/UserIdentity.php`
- `app/Http/Controllers/Admin/DashboardController.php`
- `app/Http/Controllers/Api/V1/PingController.php`
- `app/Http/Controllers/Api/V1/CurrentUserController.php`
- `app/Http/Middleware/EnsureAppUser.php`
- `app/Http/Middleware/AssignRequestId.php`
- `app/Support/Api/*`
- `app/Console/Commands/CreateAdminUser.php`
- `routes/admin.php`
- `routes/admin-settings.php`
- `routes/api.php`
- `resources/js/pages/admin/*`
- `resources/js/layouts/admin/*`
- `docs/api/openapi.yaml`
- `tests/Feature/Admin/*`
- `tests/Feature/Api/V1/*`

### 修改/重命名

- `app/Models/User.php`
- `app/Actions/Fortify/*`
- `app/Http/Controllers/Settings/*`
- `app/Http/Requests/Settings/*`
- `app/Providers/FortifyServiceProvider.php`
- `bootstrap/app.php`
- `config/auth.php`
- `config/fortify.php`
- 现有用户/2FA migrations、Factory、Seeder
- `resources/js/components/app-logo.tsx`
- `resources/js/components/app-sidebar.tsx`
- `resources/js/types/auth.ts`
- 现有 Auth、Settings、Dashboard 测试
- `composer.json`、`composer.lock`
- `package.json`、对应 lockfile

不删除现有 UI 组件库，不修改 CareNote 客户端仓库。

## 12. 风险与缓解

| 风险 | 影响 | 可能性 | 缓解 |
|---|---|---|---|
| 现有数据库已有用户，重写基线导致丢数据 | 高 | 未知 | 编码前确认空库；非空改增量迁移 |
| Fortify 改 Guard 后登录/2FA/设置回归 | 高 | 中 | 更新全部原测试并执行 `composer test` |
| Sanctum 接受管理员 Session | 高 | 中 | `EnsureAppUser` 强制主体类型并测试 |
| Spatie role guard 不一致 | 中 | 中 | AdminUser 固定 `guard_name=admin`，测试清缓存 |
| API 响应封装破坏 Laravel 分页/Resource | 中 | 中 | 保留 Resource `data/meta/links`，不二次嵌套 |
| 管理页面只隐藏菜单但未保护后端 | 高 | 中 | 每条路由使用 permission/Policy |
| Wayfinder 路由生成受前缀/命名变化影响 | 中 | 中 | 重新生成并跑 TypeScript/build |
| 第一阶段过度抽象未来 App | 中 | 中 | 仅做 client-neutral v1 和 identity provider，不建 App 专属层 |
| 新增依赖扩大安全公告 | 中 | 低 | 安装后执行 Composer audit，单独报告历史公告 |

## 13. 验收标准

- [ ] `/admin` 与 `/api/v1` 路由边界清晰。
- [ ] `AdminUser` 和 `User` 分表、分 Guard、分认证方式。
- [ ] 管理端公开注册关闭。
- [ ] RBAC 只作用于管理员。
- [ ] 小程序/API 用户不能进入管理端。
- [ ] 管理员 Session 不能冒充客户端 API 用户。
- [ ] `/api/v1/ping` 和 `/api/v1/me` 契约稳定。
- [ ] API 异常、Request ID、限流格式通过测试。
- [ ] 管理端品牌和 Dashboard 已替换为 CareNote。
- [ ] OpenAPI 校验通过。
- [ ] PHP 自动化测试通过。
- [ ] 前端 lint、类型检查和生产构建通过。
- [ ] PostgreSQL fresh migration 通过。

## 14. 本地审查

项目没有配置 `all-plan` 所需的外部 inspiration/reviewer 角色，因此按技能规则跳过外部协作者，执行本地审查。

| 维度 | 得分 |
|---|---:|
| 清晰度 | 9/10 |
| 完整性 | 9/10 |
| 可行性 | 9/10 |
| 风险覆盖 | 9/10 |
| 需求对齐 | 10/10 |
| **综合** | **9.2/10** |

审查结论：方案可实施。唯一会改变迁移方式的阻塞项，是现有数据库是否允许重建。
