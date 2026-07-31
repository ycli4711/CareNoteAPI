# CareNote API 响应契约

本文档适用于 `/api/v1/**`。HTTP 状态码表达协议层处理结果，业务响应码表达客户端需要识别的稳定业务语义。客户端不得仅根据 `message` 判断业务分支。

## 统一结构

所有带 JSON 响应体的 API 都返回以下字段：

```json
{
  "success": true,
  "code": "COMMON.OK",
  "message": "操作成功。",
  "data": {},
  "errors": {},
  "meta": {
    "request_id": "01912345-6789-7abc-def0-123456789abc"
  }
}
```

- `success`：请求是否成功。
- `code`：稳定的业务响应码；客户端可以据此处理分支。
- `message`：面向用户或开发者的可读说明，不作为程序判断依据。
- `data`：成功时为业务数据，失败时固定为 `null`。
- `errors`：失败时可包含字段级错误，其他情况为空对象。
- `meta`：响应元数据。`request_id` 用于日志追踪；分页信息也放在这里。

业务码使用 `领域.语义` 格式，采用大写字母和下划线。修改已有业务码属于破坏性 API 契约变更。

## 成功码

| HTTP | 业务码 | 含义 |
| --- | --- | --- |
| 200 | `COMMON.OK` | 查询或普通操作成功 |
| 201 | `COMMON.CREATED` | 资源创建成功 |

需要返回统一响应体的删除、退出等操作使用 `200 + COMMON.OK`，不使用无响应体的 204。

## 通用错误码

| HTTP | 业务码 | 含义 |
| --- | --- | --- |
| 400 | `COMMON.BAD_REQUEST` | 请求格式或参数语义有误 |
| 其他 4xx | `COMMON.HTTP_ERROR` | 其他未单独定义的客户端 HTTP 错误，保留原 HTTP 状态 |
| 401 | `AUTH.UNAUTHENTICATED` | 未提供有效身份凭证 |
| 403 | `AUTH.FORBIDDEN` | 已认证但缺少所需权限 |
| 404 | `COMMON.NOT_FOUND` | 路由或业务资源不存在 |
| 405 | `COMMON.METHOD_NOT_ALLOWED` | HTTP 方法不被目标路由允许 |
| 409 | `COMMON.CONFLICT` | 请求与资源当前状态冲突 |
| 413 | `COMMON.PAYLOAD_TOO_LARGE` | 请求体超过允许大小 |
| 415 | `COMMON.UNSUPPORTED_MEDIA_TYPE` | 请求媒体类型不受支持 |
| 422 | `COMMON.VALIDATION_FAILED` | 字段校验失败，详情位于 `errors` |
| 429 | `COMMON.RATE_LIMITED` | 超出请求频率限制 |
| 500 | `COMMON.INTERNAL_ERROR` | 未预期的服务端异常 |
| 502 | `UPSTREAM.BAD_GATEWAY` | 上游服务返回异常 |
| 503 | `UPSTREAM.SERVICE_UNAVAILABLE` | 服务暂时不可用 |
| 504 | `UPSTREAM.GATEWAY_TIMEOUT` | 上游服务响应超时 |

生产环境的 5xx 响应不得返回异常堆栈、内部类名、SQL 或密钥等敏感信息。

## 认证业务错误码

| HTTP | 业务码 | 含义 |
| --- | --- | --- |
| 401 | `AUTH.WECHAT_CODE_INVALID` | 微信临时登录凭证无效或过期 |
| 401 | `AUTH.REFRESH_TOKEN_INVALID` | Refresh Token 无效或类型不正确 |
| 401 | `AUTH.REFRESH_TOKEN_EXPIRED` | Refresh Token 已过期 |
| 401 | `AUTH.SESSION_REVOKED` | 当前 Token 会话已注销或检测到已轮换 Token 被重用 |
| 403 | `AUTH.ACCOUNT_DISABLED` | 当前账户已被禁用 |
| 502 | `AUTH.WECHAT_UPSTREAM_ERROR` | 微信服务返回异常结果 |
| 503 | `AUTH.WECHAT_UNAVAILABLE` | 微信服务暂时不可用 |

新增业务码时必须同时维护代码枚举、异常映射、OpenAPI 契约和对应测试。
