# API 响应生产配置模板

适用于 Hyperf 前后端分离项目的推荐生产配置。

## 1. 推荐配置

```php
// config/autoload/api-response.php
return [
    'enable_exception_handler' => true,
    'render_api_paths' => [
        '/api/*',
        '/admin/*',
    ],
    'hide_error_when_not_debug' => true,
    'fallback_success_status_code' => 200,
    'fallback_error_status_code' => 400,
    'pipes' => [
        \FeloZ\HyperfApiResponse\Support\Pipes\MessagePipe::class,
        \FeloZ\HyperfApiResponse\Support\Pipes\ErrorPipe::class,
    ],
    'exception_pipes' => [
        \FeloZ\HyperfApiResponse\Support\ExceptionPipes\BusinessExceptionPipe::class,
        \FeloZ\HyperfApiResponse\Support\ExceptionPipes\AuthenticationExceptionPipe::class,
        \FeloZ\HyperfApiResponse\Support\ExceptionPipes\HttpExceptionPipe::class,
        \FeloZ\HyperfApiResponse\Support\ExceptionPipes\ValidationExceptionPipe::class,
    ],
];
```

如果后台 admin 同时存在 HTML 页面，使用更精细的路径：

```php
'render_api_paths' => [
    '/api/*',
    '/admin/api/*',
],
```

## 2. 环境变量

```env
APP_DEBUG=false
API_RESPONSE_ENABLE_EXCEPTION_HANDLER=true
API_RESPONSE_HIDE_ERROR=true
API_RESPONSE_APP_DEBUG=false
```

未设置 `API_RESPONSE_APP_DEBUG` 时回退读取 `APP_DEBUG`。

## 3. 前端配合

- 请求统一带 `Accept: application/json`
- 判定规则：先看 `status`，失败再按 body `code` 分支（`0` = 成功，`1002` = 未登录，`1001` = 校验失败）
- HTTP 401/422 可用于 axios 拦截器，业务语义以 body `code` 为准

## 4. 排错清单

异常未自动转 JSON 时检查：

1. `API_RESPONSE_ENABLE_EXCEPTION_HANDLER` 是否为 `true`
2. 路径是否命中 `render_api_paths`（注意 Hyperf 路径带前导 `/`）
3. 请求头是否包含 `Accept: application/json`
4. `ApiExceptionHandler` 在异常处理器链中的优先级是否足够高
