# API 响应协议（前端精简版）

给前端同学的快速约定，只保留最常用内容。

## 1. 统一响应格式

```json
{
  "status": true,
  "code": 200,
  "message": "OK",
  "data": {},
  "error": {}
}
```

| 字段 | 含义 |
|------|------|
| `status` | 是否成功（`true/false`） |
| `code` | 状态码（HTTP 或业务码） |
| `message` | 展示给用户的提示文案 |
| `data` | 成功数据 |
| `error` | 错误详情（生产环境可能省略） |

## 2. 需要重点处理的状态码

| 码 | 含义 | 建议处理 |
|----|------|---------|
| 200 | 成功 | 正常渲染 |
| 201 | 创建成功 | 正常渲染 |
| 400 | 参数错误 | 提示 message |
| 401 | 未登录/登录失效 | 跳登录页 |
| 403 | 无权限 | 提示无权限 |
| 404 | 资源不存在 | 提示不存在 |
| 422 | 表单校验失败 | 读取 error 渲染字段错误 |
| 429 | 请求频繁 | 提示后延迟重试 |
| 500 | 服务异常 | 通用错误提示 |

## 3. 响应示例

### 成功

```json
{
  "status": true,
  "code": 200,
  "message": "查询成功",
  "data": { "id": 1, "name": "Alice" }
}
```

### 校验失败（422）

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

### 未登录（401）

```json
{
  "status": false,
  "code": 401,
  "message": "Unauthenticated."
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

## 4. 统一判定规则

```ts
if (resp.status === true) {
  return resp.data;
}

switch (resp.code) {
  case 401:
    // 跳转登录
    break;
  case 422:
    // 表单校验提示，读取 resp.error
    break;
  case 429:
    // 限流提示
    break;
  default:
    toast(resp.message || '请求失败');
}
```

核心原则：

1. 先看 `status`，作为成功/失败的唯一入口判断
2. 当 `status=false` 时，再按 `code` 做业务分支
3. 不要用 HTTP 状态码判业务成功失败，HTTP 仅作网络层辅助信息
