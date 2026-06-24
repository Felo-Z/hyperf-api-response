# API 响应约定模板（前后端协作）

本文档用于团队约定统一的 API 响应协议，可直接复制到业务项目中调整。

## 1. 统一响应结构

```json
{
  "status": true,
  "code": 200,
  "message": "OK",
  "data": {},
  "error": {}
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| `status` | `boolean` | 业务成功标记 |
| `code` | `integer` | 业务码或 HTTP 状态码 |
| `message` | `string` | 面向客户端的简要提示 |
| `data` | `any` | 成功结果主体（失败时为 `null`） |
| `error` | `object\|null` | 错误详情（生产环境可隐藏） |

## 2. 状态码使用约定

### 成功类

| 码 | 含义 | 典型场景 |
|----|------|---------|
| 200 | 成功 | 查询、更新 |
| 201 | 创建成功 | POST 创建资源 |
| 202 | 已受理 | 异步任务入队 |
| 204 | 无内容 | 删除成功 |

### 错误类

| 码 | 含义 | 典型场景 |
|----|------|---------|
| 400 | 请求参数非法 | 缺少必填字段 |
| 401 | 未认证 | Token 过期 |
| 403 | 无权限 | 角色不足 |
| 404 | 资源不存在 | ID 不存在 |
| 409 | 状态冲突 | 重复操作 |
| 422 | 业务校验失败 | 表单验证 |
| 429 | 限流 | 频率过高 |
| 500 | 系统异常 | 未预期错误 |

### 业务码（可选）

当需要比 HTTP 更细粒度的语义时，在 `code` 中使用业务码段：

- 通用：`1xxx`
- 用户域：`2xxxxx`
- 订单域：`3xxxxx`
- 支付域：`4xxxxx`

示例：`200404` = 用户域资源不存在，`300422` = 订单域校验失败

业务码时 HTTP 状态码规则：成功 → 200，失败 → 400。

## 3. 错误对象约定

推荐 `error` 结构：

```json
{
  "type": "validation_error",
  "details": {
    "email": ["邮箱格式不正确"]
  }
}
```

- `type`：错误类型（`validation_error`、`biz_error`、`system_error`）
- `details`：字段级错误或业务细节

## 4. 成功响应示例

### 列表查询

```json
{
  "status": true,
  "code": 200,
  "message": "OK",
  "data": {
    "list": [
      { "id": 1, "name": "Alice" },
      { "id": 2, "name": "Bob" }
    ],
    "total": 2
  }
}
```

### 创建成功

```json
{
  "status": true,
  "code": 201,
  "message": "Created",
  "data": { "id": 1001 }
}
```

## 5. 失败响应示例

### 参数校验失败（422）

```json
{
  "status": false,
  "code": 422,
  "message": "邮箱格式不正确",
  "error": {
    "email": ["邮箱格式不正确"]
  }
}
```

### 业务冲突（409）

```json
{
  "status": false,
  "code": 409,
  "message": "订单状态不可变更",
  "error": { "order_status": "paid" }
}
```

### 系统异常（500）

```json
{
  "status": false,
  "code": 500,
  "message": "Internal Server Error"
}
```

## 6. 前后端协作建议

- 前端渲染提示优先使用 `message`
- 先根据 `status` 判断成功/失败，再根据 `code` 做分支处理
- 生产环境不暴露堆栈，配置 `hide_error_when_not_debug=true`
- 所有 API 保持同一结构，避免接口间格式不一致
