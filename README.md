# Hyperf API Response

Hyperf 3.2+ 统一 API 响应与异常处理扩展包。

## 要求

- PHP >= 8.2
- Hyperf >= 3.2

## 相关项目

Laravel 侧有独立实现：[`felo-z/laravel-api-response`](https://github.com/Felo-Z/laravel-api-response)。

两包共享 Pipeline 扩展机制；本包通过 `api_response()` 获取响应构造器。**JSON 契约不同**（见下表）。混用两套后端时，前端需按各自约定处理 `code` 字段。

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

api_response()->ok(['id' => 1], 'ok');
api_response()->success(['id' => 1], 'success');
api_response()->message('创建成功', ['id' => 1], 201);
api_response()->failed('参数错误', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
api_response()->exception($throwable);
```

控制器中直接 `return`：

```php
public function show(int $id)
{
    return api_response()->ok(['id' => $id]);
}
```

## 文档

| 文档 | 说明 |
| --- | --- |
| [api-response.md](docs/api-response.md) | 完整使用手册 |
| [api-response-examples.md](docs/api-response-examples.md) | Controller / 验证 / 异常接入示例 |
| [api-response-project-extension.md](docs/api-response-project-extension.md) | **项目自建 `UserCode` / `OrderCode` 等业务码常量** |
| [api-response-frontend-quick.md](docs/api-response-frontend-quick.md) | 前端判定规则（精简版） |
| [api-response-contract-template.md](docs/api-response-contract-template.md) | 前后端协作约定模板 |
| [api-response-production-template.md](docs/api-response-production-template.md) | 生产环境配置模板 |
| [api-response-benchmark.md](docs/api-response-benchmark.md) | pipes 性能压测 |

## 响应结构

成功响应示例（`error` 字段通常不出现）：

```json
{
  "status": true,
  "code": 0,
  "message": "OK",
  "data": {}
}
```

失败响应示例（有 `$error` 或 Pipe 产出时才有 `error`）：

```json
{
  "status": false,
  "code": 1001,
  "message": "邮箱格式不正确",
  "data": null,
  "error": {
    "email": ["邮箱格式不正确"]
  }
}
```

| 字段 | 说明 |
| --- | --- |
| `status` | 业务状态（`true` / `false`），满足 `status === (code === 0)` |
| `code` | **业务码**：`0` = 成功；`1000–1999` = 包内置（`ApiCode`）；其它整数 = 项目自定义（见 [项目扩展指南](docs/api-response-project-extension.md)） |
| `message` | 提示文案 |
| `data` | 成功数据；失败时为 `null` |
| `error` | 可选；错误详情。生产环境会隐藏系统堆栈类诊断信息 |

HTTP 状态码在传输层独立控制（如 `ok()` → 200、`notFound()` → 404），**不写入** body `code`。`204` / `205` 响应无 body（符合 RFC）。

## 项目业务码

包不提供 `UserCode`、`OrderCode` 等类，需在业务项目中按域自建常量类，例如：

```php
// app/Support/ApiCodes/UserCode.php
final class UserCode
{
    public const NOT_FOUND = 200404;
}

// 使用
return api_response()->failed('用户不存在', UserCode::NOT_FOUND, 400);
```

完整创建步骤见 [docs/api-response-project-extension.md](docs/api-response-project-extension.md)。

## HTTP 状态码策略

body `code` 与 HTTP 状态码已解耦：

- **body `code`**：成功 `0`；包内置 `1000–1999`；项目自定义建议使用该区间以外的整数
- **HTTP 状态码**：由方法参数或快捷方法决定（如 `failed($msg, $code, 400, $error)`、`notFound()` → 404）

业务失败默认 HTTP `400`，避免被网关误判为系统 500；需要时可显式传入其他 HTTP 码。

## 异常自动接管

安装后通过 `ConfigProvider` 注册 `ApiExceptionHandler`，对以下请求自动将未捕获异常转为统一 JSON：

- `Accept` 包含 `application/json` 或 `+json`
- 路径匹配 `render_api_paths`（默认 `/api/*`）

业务代码只需 `throw`，无需手动 `return api_response()->exception(...)`。

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
  "data": null
}
```

若返回纯文本或非统一 JSON，说明 Handler 顺序需要调整。

#### 自定义 Handler 的放置原则

- 只处理特定业务异常（如 `BizException`）→ 放在 `ApiExceptionHandler` **之前**
- 兜底所有异常（`isValid` 恒为 `true`）→ 放在 `ApiExceptionHandler` **之后**

## 配置

配置文件：`config/autoload/api-response.php`

```php
return [
    'enable_exception_handler' => env('API_RESPONSE_ENABLE_EXCEPTION_HANDLER', true),
    'render_api_paths' => ['/api/*'],
    'hide_error_when_not_debug' => env('API_RESPONSE_HIDE_ERROR', true),
    'pipes' => [...],
    'exception_pipes' => [...],
];
```

### 环境变量

```env
API_RESPONSE_ENABLE_EXCEPTION_HANDLER=true
API_RESPONSE_HIDE_ERROR=true
API_RESPONSE_APP_DEBUG=false
```

未设置 `API_RESPONSE_APP_DEBUG` 时回退读取 `APP_DEBUG`。

## 开发

```bash
composer install
composer test   # 需要 PHP Swoole 扩展，或 swoole-cli
```

变更记录见 [CHANGELOG.md](CHANGELOG.md)。

## License

MIT
