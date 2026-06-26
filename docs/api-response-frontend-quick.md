# API 响应协议（前端精简版）

给前端同学的快速约定，只保留最常用内容。

## 1. 统一响应格式

```json
{
  "status": true,
  "code": 0,
  "message": "OK",
  "data": {}
}
```

| 字段 | 含义 |
|------|------|
| `status` | 是否成功（`true/false`），满足 `status === (code === 0)` |
| `code` | **业务码**：`0` = 成功；`1000–1999` = 包内置；如 `200404` = 项目自定义（见后端 `UserCode` 等常量类） |
| `message` | 展示给用户的提示文案 |
| `data` | 成功数据 |
| `error` | 可选；错误详情（生产环境可能省略系统诊断信息） |

HTTP 状态码（200、404 等）在传输层，**不要**用 HTTP 码判断业务成败；body `code` 才是业务语义。`204` 删除成功时响应 body 为空。

## 2. 需要重点处理的业务码

| body `code` | 含义 | 常见 HTTP | 建议处理 |
|-------------|------|-----------|---------|
| `0` | 成功 | 200/201/… | 正常渲染 `data` |
| `1000` | 通用失败 | 400 | 提示 `message` |
| `1001` | 校验失败 | 422 | 读取 `error` 渲染字段错误 |
| `1002` | 未登录 | 401 | 跳登录页 |
| `1003` | 无权限 | 403 | 提示无权限 |
| `1004` | 资源不存在 | 404 | 提示不存在 |
| `1006` | 请求频繁 | 429 | 提示后延迟重试 |
| `1999` | 系统异常 | 500 | 通用错误提示 |
| `200404` 等 | 项目业务码 | 通常 400 | 按项目约定分支 |

## 3. 响应示例

### 成功（HTTP 200，body code = 0）

```json
{
  "status": true,
  "code": 0,
  "message": "查询成功",
  "data": { "id": 1, "name": "Alice" }
}
```

### 校验失败（HTTP 422，body code = 1001）

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

### 未登录（HTTP 401，body code = 1002）

```json
{
  "status": false,
  "code": 1002,
  "message": "Unauthenticated."
}
```

### 系统异常（HTTP 500，body code = 1999）

```json
{
  "status": false,
  "code": 1999,
  "message": "Internal Server Error"
}
```

## 4. 统一判定规则

```ts
if (resp.status === true) {
  return resp.data;
}

switch (resp.code) {
  case 1002:
    // 跳转登录
    break;
  case 1001:
    // 表单校验提示，读取 resp.error
    break;
  case 1006:
    // 限流提示
    break;
  case 200404:
    // 项目自定义：用户不存在
    break;
  default:
    toast(resp.message || '请求失败');
}
```

核心原则：

1. 先看 `status`，作为成功/失败的唯一入口判断
2. 当 `status=false` 时，再按 body `code` 做业务分支
3. HTTP 状态码仅作网络层辅助（如 401 触发 axios 拦截器），业务语义以 body `code` 为准
