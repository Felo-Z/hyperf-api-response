# API 响应项目扩展指南

当项目需要比 HTTP 状态码更细粒度的业务语义时，推荐使用"项目侧扩展"方案：业务码常量 + 业务异常 + 自定义异常 Pipe + 宏方法。

## 1. 业务码常量（项目内维护）

建议在项目中按业务域定义常量类：

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiCodes;

class UserCode
{
    public const USER_NOT_FOUND = 200404;
    public const USER_ALREADY_EXISTS = 200409;
    public const USER_STATUS_INVALID = 200422;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiCodes;

class OrderCode
{
    public const ORDER_NOT_FOUND = 300404;
    public const ORDER_STATUS_INVALID = 300422;
    public const ORDER_ALREADY_PAID = 300409;
}
```

业务代码中使用：

```php
use App\Support\ApiCodes\UserCode;

return ap()->failed('用户不存在', UserCode::USER_NOT_FOUND);
```

包内也提供了通用业务码 `FeloZ\HyperfApiResponse\Support\ApiCode`（`BIZ_OK`、`BIZ_FAILED` 等），适合快速上手。

## 2. 业务异常 + Exception Pipe（推荐）

### 2.1 定义业务异常

```php
<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

class BizException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $bizCode,
        protected array $context = []
    ) {
        parent::__construct($message);
    }

    public function bizCode(): int
    {
        return $this->bizCode;
    }

    public function context(): array
    {
        return $this->context;
    }
}
```

### 2.2 定义异常 Pipe

```php
<?php

declare(strict_types=1);

namespace App\Support\ApiResponse\ExceptionPipes;

use App\Exception\BizException;
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
        ] + $structure;
    }
}
```

### 2.3 注册到配置

```php
// config/autoload/felo-api-response.php
'exception_pipes' => [
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\AuthenticationExceptionPipe::class,
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\HttpExceptionPipe::class,
    \FeloZ\HyperfApiResponse\Support\ExceptionPipes\ValidationExceptionPipe::class,
    \App\Support\ApiResponse\ExceptionPipes\BizExceptionPipe::class,
],
```

### 2.4 业务代码中使用

```php
use App\Exception\BizException;
use App\Support\ApiCodes\UserCode;

throw new BizException('用户不存在', UserCode::USER_NOT_FOUND, ['user_id' => $id]);
```

异常由 `ApiExceptionHandler` 自动接管，无需手动 `return ap()->exception(...)`。

## 3. 宏方法扩展

`ApiResponse` 支持 `Macroable`，可注册自定义方法：

```php
use FeloZ\HyperfApiResponse\Support\ApiResponse;

// 在 Listener 或 bootstrap 中注册
ApiResponse::macro('userNotFound', function () {
    /** @var ApiResponse $this */
    return $this->failed('用户不存在', 200404, ['type' => 'biz_error']);
});

// 业务中使用
return ap()->userNotFound();
```

## 4. 替换实现（重度定制）

如果项目需要完全不同的响应结构，可在 `dependencies` 中重绑契约：

```php
// config/autoload/dependencies.php
return [
    \FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract::class => \App\Support\ProjectApiResponse::class,
];
```

## 5. 推荐目录结构

```text
app/
├── Exception/
│   └── BizException.php
├── Support/
│   ├── ApiCodes/
│   │   ├── UserCode.php
│   │   └── OrderCode.php
│   └── ApiResponse/
│       ├── Pipes/
│       └── ExceptionPipes/
│           └── BizExceptionPipe.php
```

## 6. 设计原则

- 包层保持通用：`ok/failed/exception/json` + pipes 机制
- 项目层承接差异：业务码常量、业务异常、宏方法
- 避免在包里堆积项目特有 code 语义
