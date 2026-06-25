# API 响应接入示例

本文档提供 Hyperf 业务项目中的典型接入方式，建议结合 [api-response.md](api-response.md) 一起阅读。

## 1. Controller 基础示例

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: '/api/users')]
class UserController
{
    #[GetMapping(path: '{id}')]
    public function show(int $id): ResponseInterface
    {
        $user = User::query()->find($id);
        if (! $user) {
            return ap()->notFound('用户不存在');
        }

        return ap()->ok($user->toArray(), '查询成功');
    }

    #[PostMapping(path: '')]
    public function store(RequestInterface $request): ResponseInterface
    {
        $user = User::query()->create($request->all());

        return ap()->created($user->toArray(), '创建成功', "/api/users/{$user->id}");
    }
}
```

## 2. 表单验证失败

安装 `hyperf/validation` 后，验证失败由 `ValidationExceptionPipe` 自动处理：

```php
<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email'],
        ];
    }
}
```

控制器中注入 FormRequest：

```php
#[PostMapping(path: '')]
public function store(CreateUserRequest $request): ResponseInterface
{
    $user = User::query()->create($request->validated());
    return ap()->created($user->toArray(), '创建成功');
}
```

验证失败时自动返回统一 JSON 响应。

## 3. 业务异常示例

推荐实现 `BusinessThrowable` 接口，由内置 `BusinessExceptionPipe` 自动处理：

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

### 在 Service 中抛出

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BizException;
use App\Model\Order;

class OrderService
{
    public function cancel(int $orderId): void
    {
        $order = Order::query()->find($orderId);
        if (! $order) {
            throw new BizException('订单不存在', 300404);
        }

        if ($order->status === 'paid') {
            throw new BizException('已支付订单不可取消', 300422, ['status' => $order->status]);
        }

        $order->update(['status' => 'canceled']);
    }
}
```

控制器中只需正常调用，异常由 `ApiExceptionHandler` 自动接管：

```php
#[PostMapping(path: '{id}/cancel')]
public function cancel(int $id, OrderService $service): ResponseInterface
{
    $service->cancel($id);
    return ap()->ok(null, '取消成功');
}
```

## 4. Debug 场景

```php
return ap()->debug(
    ['sql' => $query, 'bindings' => $bindings],
    '调试信息',
    500  // HTTP 状态码
);
```

- `APP_DEBUG=true` 时返回调试详情
- 生产环境 `hide_error_when_not_debug=true` 仅隐藏系统诊断类 `error`（堆栈等），校验/业务 `error` 仍会返回

## 5. 常见问题

### Q: 异常没有返回统一 JSON？

检查：

1. `FELO_API_ENABLE_EXCEPTION_HANDLER` 是否为 `true`
2. 请求路径是否命中 `render_api_paths`（注意带前导 `/`）
3. 请求头是否包含 `Accept: application/json`
4. `ApiExceptionHandler` 是否注册在其他 Handler 之前（优先级）

### Q: 如何对不同模块使用不同错误码？

建议使用"业务异常 + 自定义 exception pipe"的方式，详见 [项目扩展指南](api-response-project-extension.md)。
