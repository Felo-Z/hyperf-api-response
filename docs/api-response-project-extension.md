# API 响应项目扩展指南

当项目需要比框架通用码更细粒度的业务语义时，推荐使用「项目侧扩展」方案：**业务码常量类**（如 `UserCode`、`OrderCode`）+ 业务异常 + 自定义异常 Pipe + 宏方法。

> **说明**：`UserCode`、`OrderCode` 等常量类**不在本包内**，需在业务项目中自行创建与维护。

## 1. 业务码约定（以代码为准）

本包对 body `code` 的**运行时规则**如下：

| 规则 | 说明 |
|------|------|
| 成功 | `code` 必须为 `0`（`ApiCode::BIZ_OK`） |
| 失败 | 任意非 `0` 整数；传入 `0` 时 `failed()` 会自动改为 `1000` |
| `status` | 由 `code === 0` 推导，不可手动矛盾 |
| HTTP 状态码 | 与 body `code` 独立，由参数或 Pipe 控制 |

包内置常量 `ApiCode` 占用 **1000–1999**（框架/通用错误）。**项目自定义码建议使用该区间以外的整数**，避免与内置 Pipe、快捷方法冲突；包**不会**在运行时校验具体数值或分段规则。

### 1.1 包内置码（`ApiCode`）

| 常量 | 值 | 典型场景 |
|------|-----|---------|
| `BIZ_OK` | 0 | 成功 |
| `BIZ_FAILED` | 1000 | 通用失败 |
| `BIZ_VALIDATION_ERROR` | 1001 | 校验失败 |
| `BIZ_UNAUTHORIZED` | 1002 | 未认证 |
| `BIZ_FORBIDDEN` | 1003 | 无权限 |
| `BIZ_NOT_FOUND` | 1004 | 资源不存在 |
| `BIZ_CONFLICT` | 1005 | 冲突 |
| `BIZ_TOO_MANY_REQUESTS` | 1006 | 限流 |
| `BIZ_SYSTEM_ERROR` | 1999 | 未捕获系统异常 |

完整列表见 `FeloZ\HyperfApiResponse\Support\ApiCode`。

### 1.2 项目自定义码（自行约定）

推荐按**业务域分段**命名常量，便于前后端协作。以下为常见约定示例（非强制）：

| 域 | 前缀示例 | 示例常量 | 含义 |
|----|---------|---------|------|
| 用户 | `2xxxxx` | `200404` | 用户不存在 |
| 订单 | `3xxxxx` | `300422` | 订单状态无效 |
| 支付 | `4xxxxx` | `400409` | 重复支付 |

团队可自定规则，只要**避开 1000–1999** 并与前端对齐即可。

---

## 2. 创建业务码常量类

在业务项目中新建目录与文件（路径可调整，保持团队统一即可）：

```text
app/Support/ApiCodes/
├── UserCode.php
└── OrderCode.php
```

### 2.1 `UserCode`

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiCodes;

/**
 * 用户域业务码。
 *
 * 约定：2xxxxx 段；勿使用 1000–1999（包预留）。
 */
final class UserCode
{
    public const NOT_FOUND = 200404;

    public const ALREADY_EXISTS = 200409;

    public const STATUS_INVALID = 200422;
}
```

### 2.2 `OrderCode`

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiCodes;

/**
 * 订单域业务码。
 *
 * 约定：3xxxxx 段。
 */
final class OrderCode
{
    public const NOT_FOUND = 300404;

    public const STATUS_INVALID = 300422;

    public const ALREADY_PAID = 300409;
}
```

**创建要点**：

- 使用 `final class` + `public const`，避免运行时被继承篡改
- 常量名用领域语义（`NOT_FOUND`），数值用域前缀 + 语义后缀（如 `200404`）
- 新增错误码时同步更新前端分支表（见 [前端精简版](api-response-frontend-quick.md)）

---

## 3. 使用业务码常量

### 3.1 控制器中直接返回

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\ApiCodes\UserCode;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: '/api/users')]
class UserController
{
    #[GetMapping(path: '{id}')]
    public function show(int $id): ResponseInterface
    {
        $user = User::query()->find($id);
        if (! $user) {
            // 第 2 参：body code；第 3 参：HTTP 状态码（默认 400）
            return api_response()->failed('用户不存在', UserCode::NOT_FOUND, 400);
        }

        return api_response()->ok($user->toArray(), '查询成功');
    }
}
```

### 3.2 与业务异常配合（推荐）

先创建项目级异常（一次）：

```php
<?php

declare(strict_types=1);

namespace App\Exception;

use FeloZ\HyperfApiResponse\Support\Contracts\BusinessThrowable;
use RuntimeException;

class BizException extends RuntimeException implements BusinessThrowable
{
    public function __construct(
        string $message,
        protected int $businessCode,
        protected ?array $errorData = null
    ) {
        parent::__construct($message);
    }

    public function getBusinessCode(): int
    {
        return $this->businessCode;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}
```

Service 中抛出，使用常量类：

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BizException;
use App\Support\ApiCodes\OrderCode;

class OrderService
{
    public function cancel(int $orderId): void
    {
        $order = Order::query()->find($orderId);
        if (! $order) {
            throw new BizException('订单不存在', OrderCode::NOT_FOUND);
        }

        if ($order->status === 'paid') {
            throw new BizException(
                '已支付订单不可取消',
                OrderCode::ALREADY_PAID,
                ['status' => $order->status]
            );
        }

        $order->update(['status' => 'canceled']);
    }
}
```

控制器无需捕获，由 `ApiExceptionHandler` 自动转为统一 JSON。`BusinessExceptionPipe` 会将 `getBusinessCode()` 写入 body `code`，默认 HTTP `400`；若需 HTTP `404` 等，见下文自定义 Pipe。

### 3.3 与宏方法配合（可选）

在 Listener 或 bootstrap 中注册，内部引用常量：

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use App\Support\ApiCodes\UserCode;
use FeloZ\HyperfApiResponse\Support\ApiResponse;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

class ApiResponseMacroListener implements ListenerInterface
{
    public function listen(): array
    {
        return [BootApplication::class];
    }

    public function process(object $event): void
    {
        ApiResponse::macro('userNotFound', function () {
            /** @var ApiResponse $this */
            return $this->failed('用户不存在', UserCode::NOT_FOUND, 400);
        });
    }
}
```

业务中使用：

```php
return api_response()->userNotFound();
```

---

## 4. 业务异常进阶

### 4.1 方式 A：实现 `BusinessThrowable`（内置 Pipe 自动识别）

配置中已默认注册 `BusinessExceptionPipe`，实现该接口的异常会自动映射为统一 JSON（body `code` = `getBusinessCode()`，默认 HTTP `400`）。

### 4.2 方式 B：自定义 Exception Pipe

适用于已有异常类、不便改接口，或需自定义 HTTP 状态码的情况：

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiResponse\ExceptionPipes;

use App\Exception\LegacyBizException;
use App\Support\ApiCodes\UserCode;
use Closure;
use Throwable;

class LegacyBizExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        if (! $throwable instanceof LegacyBizException) {
            return $structure;
        }

        $httpStatus = $throwable->bizCode() === UserCode::NOT_FOUND ? 404 : 400;

        return [
            'code' => $throwable->bizCode(),
            'message' => $throwable->getMessage(),
            'error' => $throwable->context(),
            'http_status' => $httpStatus,
        ] + $structure;
    }
}
```

### 4.3 注册到配置（方式 B）

```php
// config/autoload/api-response.php
'exception_pipes' => [
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\BusinessExceptionPipe::class,
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\AuthenticationExceptionPipe::class,
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\HttpExceptionPipe::class,
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\ValidationExceptionPipe::class,
    \App\Support\ApiResponse\ExceptionPipes\LegacyBizExceptionPipe::class,
],
```

---

## 5. 宏方法扩展

`ApiResponse` 支持 `Macroable`，详见上文 §3.3。完整说明见 [api-response.md §10](api-response.md#10-宏扩展)。

---

## 6. 替换实现（重度定制）

如果项目需要完全不同的响应结构，可在 `dependencies` 中重绑契约：

```php
// config/autoload/dependencies.php
return [
    \FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract::class => \App\Support\ProjectApiResponse::class,
];
```

---

## 7. 推荐目录结构

```text
app/
├── Exception/
│   └── BizException.php
├── Listener/
│   └── ApiResponseMacroListener.php   # 可选
├── Support/
│   ├── ApiCodes/
│   │   ├── UserCode.php               # 项目自建
│   │   └── OrderCode.php              # 项目自建
│   └── ApiResponse/
│       ├── Pipes/
│       └── ExceptionPipes/
│           └── LegacyBizExceptionPipe.php
```

---

## 8. 设计原则

- 包层保持通用：`ok` / `failed` / `exception` / `json` + pipes 机制；内置码仅用 `1000–1999`
- 项目层承接差异：在 `app/Support/ApiCodes/` 维护 `UserCode`、`OrderCode` 等常量类
- 避免在包里堆积项目特有 code 语义
- 新增/变更业务码时，同步更新前端约定文档
