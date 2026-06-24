# Hyperf API Response

Hyperf 3.2+ 统一 API 响应与异常处理扩展包，JSON 契约与 `felo-z/laravel-helper` 对齐。

## 要求

- PHP >= 8.2
- Hyperf >= 3.2

## 安装

```bash
composer require felo-z/hyperf-api-response
```

发布配置（可选，包已内置默认配置）：

```bash
php bin/hyperf.php vendor:publish felo-z/hyperf-api-response
```

## 快速使用

```php
ap()->ok(['id' => 1], 'ok');
ap()->success(['id' => 1], 'success');
ap()->message('created', 201, ['id' => 1]);
ap()->failed('bad request', 400, ['field' => 'name']);
ap()->exception($throwable);
```

控制器中直接 `return`：

```php
public function show(int $id)
{
    return ap()->ok(['id' => $id]);
}
```

## 响应结构

```json
{
  "status": true,
  "code": 200,
  "message": "OK",
  "data": {},
  "error": {}
}
```

| 字段 | 说明 |
| --- | --- |
| `status` | 业务状态（`true` / `false`） |
| `code` | 业务码或 HTTP 状态码 |
| `message` | 提示文案 |
| `data` | 成功数据 |
| `error` | 错误详情（非 debug 可按配置隐藏） |

## HTTP 状态码策略（smart）

- `code` 在 `100–599`：HTTP 状态码 = `code`
- `code` 为业务码：成功 → HTTP `200`，失败 → HTTP `400`

避免业务失败被误判为系统 500。

## 异常自动接管

安装后通过 `ConfigProvider` 注册 `ApiExceptionHandler`，对以下请求自动将未捕获异常转为统一 JSON：

- `Accept` 包含 `application/json` 或 `+json`
- 路径匹配 `render_api_paths`（默认 `/api/*`）

业务代码只需 `throw`，无需手动 `return ap()->exception(...)`。

### 内置 exception_pipes

| Pipe | 异常类型 |
| --- | --- |
| `AuthenticationExceptionPipe` | `Hyperf\Auth\Exception\UnauthorizedException` |
| `HttpExceptionPipe` | `Hyperf\HttpMessage\Exception\HttpException` |
| `ValidationExceptionPipe` | `Hyperf\Validation\ValidationException` |

## 配置

配置文件：`config/autoload/felo-api-response.php`

```php
return [
    'api_response' => [
        'enable_exception_handler' => env('FELO_API_ENABLE_EXCEPTION_HANDLER', true),
        'render_api_paths' => ['/api/*'],
        'hide_error_when_not_debug' => env('FELO_API_HIDE_ERROR', true),
        'pipes' => [...],
        'exception_pipes' => [...],
    ],
];
```

### 环境变量

```env
FELO_API_ENABLE_EXCEPTION_HANDLER=true
FELO_API_HIDE_ERROR=true
APP_DEBUG=false
```

## 与 hyperf-helper 的关系

本包可独立使用。若同时安装 `felo-z/hyperf-helper`，两者互补：

| 包 | 职责 |
| --- | --- |
| `hyperf-helper` | 日志、容器、HTTP 等全局 helper |
| `hyperf-api-response` | 统一 API 响应 + 异常接管 |

## 开发

```bash
composer install
composer test   # 需要 swoole-cli
```

## License

MIT
