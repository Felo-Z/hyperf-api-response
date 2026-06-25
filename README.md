# Hyperf API Response

Hyperf 3.2+ 统一 API 响应与异常处理扩展包。

## 要求

- PHP >= 8.2
- Hyperf >= 3.2

## 相关项目

Laravel 侧有独立实现：[`felo-z/laravel-api-response`](https://github.com/Felo-Z/laravel-api-response)。

两包共享 `ap()` 调用方式与 Pipeline 扩展机制，但 **JSON 契约不同**（见下表）。混用两套后端时，前端需按各自约定处理 `code` 字段。

| | Hyperf 版（本包） | Laravel 版 |
| --- | --- | --- |
| 成功 body `code` | `0`（`ApiCode::BIZ_OK`） | HTTP 状态码（如 `200`） |
| 失败 body `code` | 业务码（如 `1004`、`200404`） | HTTP 状态码（如 `404`） |
| HTTP 状态码 | 独立参数 / 快捷方法控制 | 由 body `code` 推导 |

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
use FeloZ\HyperfApiResponse\Support\ApiCode;

ap()->ok(['id' => 1], 'ok');
ap()->success(['id' => 1], 'success');
ap()->message('创建成功', ['id' => 1], 201);
ap()->failed('参数错误', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
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
  "code": 0,
  "message": "OK",
  "data": {},
  "error": {}
}
```

| 字段 | 说明 |
| --- | --- |
| `status` | 业务状态（`true` / `false`），满足 `status === (code === 0)` |
| `code` | **业务码**（`0` = 成功，`1000+` = 框架/业务错误，项目域码建议 `≥ 10000`） |
| `message` | 提示文案 |
| `data` | 成功数据 |
| `error` | 错误详情（生产环境仅隐藏系统堆栈类诊断信息） |

HTTP 状态码在传输层独立控制（如 `ok()` → 200、`notFound()` → 404），**不写入** body `code`。

## HTTP 状态码策略

body `code` 与 HTTP 状态码已解耦：

- **body `code`**：始终为业务码（成功 `0`，失败 `1000+` 或项目自定义码）
- **HTTP 状态码**：由方法参数或快捷方法决定（如 `failed($msg, $code, 400, $error)`、`notFound()` → 404）

业务失败默认 HTTP `400`，避免被网关误判为系统 500；需要时可显式传入其他 HTTP 码。

## 异常自动接管

安装后通过 `ConfigProvider` 注册 `ApiExceptionHandler`，对以下请求自动将未捕获异常转为统一 JSON：

- `Accept` 包含 `application/json` 或 `+json`
- 路径匹配 `render_api_paths`（默认 `/api/*`）

业务代码只需 `throw`，无需手动 `return ap()->exception(...)`。

### 内置 exception_pipes

| Pipe | 异常类型 |
| --- | --- |
| `BusinessExceptionPipe` | 实现 `BusinessThrowable` 契约的业务异常 |
| `AuthenticationExceptionPipe` | `Hyperf\Auth\Exception\UnauthorizedException` |
| `HttpExceptionPipe` | `Hyperf\HttpMessage\Exception\HttpException` |
| `ValidationExceptionPipe` | `Hyperf\Validation\ValidationException` |

### Exception Handler 顺序

Hyperf 会按 `config/autoload/exceptions.php` 中 `http` 数组的**先后顺序**依次尝试 Handler：先调用 `isValid()`，为 `true` 则执行 `handle()`；若 Handler 调用了 `stopPropagation()`，后续 Handler 不再执行。

本包的 `ApiExceptionHandler` 行为如下：

| 方法 | 行为 |
| --- | --- |
| `isValid()` | 仅 API 请求返回 `true`（`Accept: application/json` 或路径匹配 `/api/*`） |
| `handle()` | 返回统一 JSON，并调用 `stopPropagation()` |

非 API 请求（如后台页面）会自动跳过，交给后续 Handler 处理。

#### 推荐顺序

`ApiExceptionHandler` 应排在会提前 `stopPropagation()`、且可能先于本包处理 API 异常的 Handler **之前**，尤其是 `HttpExceptionHandler` 和兜底的 `AppExceptionHandler`。

```php
// config/autoload/exceptions.php
return [
    'handler' => [
        'http' => [
            // 可选：项目自定义业务异常（只处理特定异常类）
            // App\Exception\Handler\BizExceptionHandler::class,

            // 本包：API 统一 JSON（应在 HttpExceptionHandler 之前）
            \FeloZ\HyperfApiResponse\Exception\Handler\ApiExceptionHandler::class,

            // 框架默认
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,

            // 项目兜底
            App\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];
```

| 顺序错误 | 典型现象 |
| --- | --- |
| `HttpExceptionHandler` 在本包之前 | `/api/xxx` 404 返回纯文本，而非统一 JSON |
| `AppExceptionHandler` 在本包之前且 `isValid` 恒为 `true` | 所有异常被项目 Handler 拦截，本包不生效 |

#### 默认安装通常无需调整

本包通过 `ConfigProvider` 注册 Handler。Hyperf 合并配置时，组件 Provider 先于 `config/autoload/exceptions.php`，因此常见最终顺序为：

```
ApiExceptionHandler → HttpExceptionHandler → AppExceptionHandler
```

仅在以下情况需要手动检查：修改过 Handler 顺序、使用了 `#[ExceptionHandler]` 注解、或存在 catch-all 的 `AppExceptionHandler`。

#### 如何检查

**1. 查看配置文件**

确认 `ApiExceptionHandler` 位于 `HttpExceptionHandler` 和 `AppExceptionHandler` 之前。

**2. 查看运行时顺序**

```php
var_dump(config('exceptions.handler.http'));
```

期望类似：

```php
[
    0 => 'FeloZ\HyperfApiResponse\Exception\Handler\ApiExceptionHandler',
    1 => 'Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler',
    2 => 'App\Exception\Handler\AppExceptionHandler',
]
```

**3. 功能验证（最可靠）**

```bash
curl -i -H "Accept: application/json" http://127.0.0.1:9501/api/not-exists
```

顺序正确时，响应应包含统一 JSON 结构（HTTP 404，body `code` 为 `1004`）：

```json
{
  "status": false,
  "code": 1004,
  "message": "Not Found",
  "data": null,
  "error": {}
}
```

若返回纯文本或非统一 JSON，说明 Handler 顺序需要调整。

#### 自定义 Handler 的放置原则

- 只处理特定业务异常（如 `BizException`）→ 放在 `ApiExceptionHandler` **之前**
- 兜底所有异常（`isValid` 恒为 `true`）→ 放在 `ApiExceptionHandler` **之后**

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

## 开发

```bash
composer install
composer test   # 需要 PHP Swoole 扩展，或 swoole-cli
```

变更记录见 [CHANGELOG.md](CHANGELOG.md)。

## License

MIT
