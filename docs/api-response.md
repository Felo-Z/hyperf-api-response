# API 响应使用说明

本文档说明 `felo-z/hyperf-api-response` 的用法，包括 `ap()` 辅助函数、异常处理器、快捷状态方法和可扩展配置。

相关文档：

- [业务接入示例](api-response-examples.md)
- [前后端约定模板](api-response-contract-template.md)
- [项目扩展指南](api-response-project-extension.md)
- [前端判定规则](api-response-frontend-quick.md)
- [性能压测指南](api-response-benchmark.md)
- [生产配置模板](api-response-production-template.md)

## 1. 安装

```bash
composer require felo-z/hyperf-api-response
```

发布配置文件（可选）：

```bash
php bin/hyperf.php vendor:publish felo-z/hyperf-api-response
```

## 2. 快速开始

```php
use FeloZ\HyperfApiResponse\Support\ApiCode;

// 全局辅助函数
ap()->ok(['id' => 1], '查询成功');
ap()->created($user, '创建成功', '/api/users/1');
ap()->failed('参数错误', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
ap()->exception(new \Exception('boom'));
```

## 3. 响应结构

所有响应遵循统一 JSON 结构：

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
|------|------|
| `status` | 业务状态，`true` 或 `false`，满足 `status === (code === 0)` |
| `code` | **业务码**（`0` = 成功，`1000+` = 框架错误，项目域码建议 `≥ 10000`） |
| `message` | 面向客户端的提示文案 |
| `data` | 成功数据（失败时为 `null`） |
| `error` | 错误详情（生产环境可隐藏） |

HTTP 状态码在传输层独立存在，不写入 body `code`。

## 4. 核心方法

| 方法 | 场景 | 说明 |
|------|------|------|
| `ok($data, $message)` | 常规查询成功 | HTTP 200，body `code` = 0 |
| `created($data, $message, $location)` | 创建成功 | HTTP 201，可带 `Location` 头 |
| `accepted($data, $message)` | 异步受理 | HTTP 202 |
| `noContent($message)` | 删除成功 | HTTP 204，**无响应 body**（符合 RFC） |
| `resetContent($data, $message)` | 重置内容 | HTTP 205，**无响应 body**（符合 RFC） |
| `success($data, $message, $httpStatus)` | 通用成功 | HTTP 可自定义，body `code` 恒为 0 |
| `message($message, $data, $httpStatus)` | 文案优先成功 | body `code` 恒为 0，HTTP 由第 3 参控制 |
| `failed($message, $code, $httpStatus, $error)` | 通用失败 | 推荐主入口 |
| `error($message, $code, $httpStatus, $error)` | 通用失败 | `failed` 别名 |
| `debug($payload, $message, $httpStatus)` | 调试输出 | 仅 debug 模式显示详情 |
| `exception($throwable)` | 异常转统一响应 | 走 exception_pipes |
| `json($code, $message, $data, $error, $httpStatus)` | 底层方法 | `status` 由 `code` 自动推导 |

## 5. HTTP 快捷方法

快捷方法同时设置 HTTP 状态码与对应的框架业务码（body `code`）：

### 成功态

`ok()` → HTTP 200、`created()` → 201、`accepted()` → 202、`nonAuthoritativeInformation()` → 203、`noContent()` → 204、`resetContent()` → 205、`partialContent()` → 206、`multiStatus()` → 207、`alreadyReported()` → 208、`imUsed()` → 226

成功时 body `code` 均为 `0`（`ApiCode::BIZ_OK`）。

### 错误态

`badRequest()` → HTTP 400 / `BIZ_FAILED`、`unauthorized()` → 401 / `BIZ_UNAUTHORIZED`、`forbidden()` → 403 / `BIZ_FORBIDDEN`、`notFound()` → 404 / `BIZ_NOT_FOUND`、`unprocessableEntity()` → 422 / `BIZ_VALIDATION_ERROR`、`tooManyRequests()` → 429 / `BIZ_TOO_MANY_REQUESTS`、`internalServerError()` → 500 / `BIZ_SYSTEM_ERROR` 等。

完整列表见 `ApiResponse` 类源码。

## 6. 异常自动接管

包注册了 `ApiExceptionHandler`，通过 `RequestClassifier` 判断是否接管：

- 请求头包含 `application/json` 或 `+json`
- 或请求路径命中 `render_api_paths`（默认 `/api/*`）

满足条件时，异常自动转为统一 JSON 响应，无需手动捕获。

可通过 `FELO_API_ENABLE_EXCEPTION_HANDLER=false` 关闭。

## 7. 配置说明

```php
// config/autoload/felo-api-response.php
'api_response' => [
    'enable_exception_handler' => (bool) env('FELO_API_ENABLE_EXCEPTION_HANDLER', true),
    'render_api_paths' => ['/api/*'],
        'hide_error_when_not_debug' => (bool) env('FELO_API_HIDE_ERROR', true),
        'app_debug' => (bool) env('APP_DEBUG', false),
        'fallback_success_status_code' => 200,
    'fallback_error_status_code' => 400,
    'pipes' => [
        MessagePipe::class,
        ErrorPipe::class,
    ],
    'exception_pipes' => [
        BusinessExceptionPipe::class,
        AuthenticationExceptionPipe::class,
        HttpExceptionPipe::class,
        ValidationExceptionPipe::class,
    ],
],
```

### HTTP 状态码策略

body `code` 与 HTTP 状态码已解耦：

- **body `code`**：业务码，由 `failed()` / 快捷方法 / Exception Pipe 写入
- **HTTP 状态码**：由 `$httpStatus` 参数、快捷方法或 Exception Pipe 的 `http_status` 字段决定
- `fallback_success_status_code` / `fallback_error_status_code`：当 structure 缺少 `http_status` 时，由 `destination()` 兜底（正常路径由 `buildJson()` 注入）

## 8. Pipe 扩展

### 自定义响应 Pipe

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiResponse\Pipes;

use Closure;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TraceIdPipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        $response = $next($structure);
        $request = Context::get(ServerRequestInterface::class);
        $traceId = $request?->getHeaderLine('X-Trace-Id') ?: uniqid('trace_', true);

        return $response->withHeader('X-Trace-Id', $traceId);
    }
}
```

### 自定义异常 Pipe

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiResponse\ExceptionPipes;

use App\Exceptions\BizException;
use Closure;
use Throwable;

class BizExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);
        if (! $throwable instanceof BizException) {
            return $structure;
        }

        return [
            'code' => $throwable->bizCode(),
            'message' => $throwable->getMessage(),
            'error' => $throwable->context(),
            'http_status' => 400,
        ] + $structure;
    }
}
```

注册到配置中对应的 `pipes` 或 `exception_pipes` 数组即可。

## 9. 内置业务码常量

包内提供 `FeloZ\HyperfApiResponse\Support\ApiCode` 作为通用业务码参考：

| 常量 | 值 | 说明 |
|------|------|------|
| `BIZ_OK` | 0 | 业务成功 |
| `BIZ_FAILED` | 1000 | 通用业务失败 |
| `BIZ_VALIDATION_ERROR` | 1001 | 参数校验失败 |
| `BIZ_UNAUTHORIZED` | 1002 | 未认证 |
| `BIZ_FORBIDDEN` | 1003 | 无权限 |
| `BIZ_NOT_FOUND` | 1004 | 资源不存在 |
| `BIZ_CONFLICT` | 1005 | 数据冲突 |
| `BIZ_TOO_MANY_REQUESTS` | 1006 | 请求频率过高 |
| `BIZ_SYSTEM_ERROR` | 1999 | 系统异常 |

## 10. 宏扩展

`ApiResponse` 支持 `Macroable`，可注册自定义方法：

```php
use FeloZ\HyperfApiResponse\Support\ApiResponse;

ApiResponse::macro('userNotFound', function () {
    return $this->failed('用户不存在', 200404, 400, ['type' => 'biz_error']);
});

// 使用
ap()->userNotFound();
```

## 11. 执行流程

### `ap()->failed()` 流程

```
failed(message, bizCode, httpStatus, error)
  → buildJson(bizCode, message, null, error, httpStatus)
    → Pipeline: MessagePipe → ErrorPipe
      → destination(): 推导 status/http_status，生成 PSR-7 Response
```

### `ap()->exception()` 流程

```
exception(throwable)
  → Pipeline: BusinessExceptionPipe → AuthenticationExceptionPipe → HttpExceptionPipe → ValidationExceptionPipe
    → exceptionDestination(): 生成 code/message/error/http_status/headers 结构
  → buildJson(code, message, null, error, http_status) + withHeaders(headers)
    → 进入普通 pipes 流程
```

## 12. 使用建议

- 业务代码优先使用快捷方法（如 `ok()`、`notFound()`），避免手写状态码
- 统一错误输出建议直接抛异常，交给 `ApiExceptionHandler` 自动接管
- 生产环境保持 `hide_error_when_not_debug = true`，仅隐藏系统诊断类 `error`（堆栈等），校验/业务 `error` 仍会返回
- 对外 API 使用 `/api/*` 前缀，便于异常接管策略统一生效
