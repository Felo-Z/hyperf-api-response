# API 响应约定模板（前后端协作）

本文档用于团队约定统一的 API 响应协议，可直接复制到业务项目中调整。

## 1. 统一响应结构

```json
{
  "status": true,
  "code": 0,
  "message": "OK",
  "data": {},
  "error": {}
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| `status` | `boolean` | 业务成功标记，满足 `status === (code === 0)` |
| `code` | `integer` | **业务码**（`0` = 成功，非 0 = 失败） |
| `message` | `string` | 面向客户端的简要提示 |
| `data` | `any` | 成功结果主体（失败时为 `null`） |
| `error` | `object\|null` | 错误详情（生产环境可隐藏） |

HTTP 状态码在传输层独立控制，不写入 body `code`。

## 2. 业务码使用约定

### 框架层（包内置）

| 码 | 含义 | 常见 HTTP |
|----|------|-----------|
| `0` | 成功 | 200/201/… |
| `1000` | 通用失败 | 400 |
| `1001` | 校验失败 | 422 |
| `1002` | 未认证 | 401 |
| `1003` | 无权限 | 403 |
| `1004` | 资源不存在 | 404 |
| `1005` | 数据冲突 | 409 |
| `1006` | 限流 | 429 |
| `1999` | 系统异常 | 500 |

### 项目业务码（建议 ≥ 10000）

按业务域分段：

- 用户域：`2xxxxx`（如 `200404` = 用户不存在）
- 订单域：`3xxxxx`（如 `300422` = 订单状态无效）
- 支付域：`4xxxxx`

业务码失败时 HTTP 状态码默认 `400`；需要时可显式指定（如 404、422）。

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

校验失败时 `error` 也可直接使用 Laravel/Hyperf 风格的字段映射：

```json
{
  "email": ["邮箱格式不正确"]
}
```

## 4. 成功响应示例

### 列表查询（HTTP 200）

```json
{
  "status": true,
  "code": 0,
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

### 创建成功（HTTP 201）

```json
{
  "status": true,
  "code": 0,
  "message": "Created",
  "data": { "id": 1001 }
}
```

## 5. 失败响应示例

### 参数校验失败（HTTP 422，code 1001）

```json
{
  "status": false,
  "code": 1001,
  "message": "邮箱格式不正确",
  "error": {
    "email": ["邮箱格式不正确"]
  }
}
```

### 业务冲突（HTTP 400，code 300409）

```json
{
  "status": false,
  "code": 300409,
  "message": "订单已支付，不可取消",
  "error": { "order_status": "paid" }
}
```

### 系统异常（HTTP 500，code 1999）

```json
{
  "status": false,
  "code": 1999,
  "message": "Internal Server Error"
}
```

## 6. 前后端协作建议

- 前端渲染提示优先使用 `message`
- 先根据 `status` 判断成功/失败，再根据 body `code` 做分支处理
- HTTP 状态码用于网络层（拦截器、网关），业务语义以 body `code` 为准
- 生产环境不暴露堆栈，配置 `hide_error_when_not_debug=true`（校验/业务 `error` 不受影响）
- 所有 API 保持同一结构，避免接口间格式不一致
