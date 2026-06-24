# API 响应生产配置模板

适用于 Hyperf 前后端分离项目的推荐生产配置。

## 1. 推荐配置

```php
// config/autoload/felo-api-response.php
return [
    'api_response' => [
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
            \FeloZ\HyperfApiResponse\Support\Pipes\StatusCodePipe::class,
        ],
        'exception_pipes' => [
            \FeloZ\HyperfApiResponse\Support\ExceptionPipes\AuthenticationExceptionPipe::class,
            \FeloZ\HyperfApiResponse\Support\ExceptionPipes\HttpExceptionPipe::class,
            \FeloZ\HyperfApiResponse\Support\ExceptionPipes\ValidationExceptionPipe::class,
        ],
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
FELO_API_ENABLE_EXCEPTION_HANDLER=true
FELO_API_HIDE_ERROR=true
```

## 3. 前端配合

- 请求统一带 `Accept: application/json`
- 判定规则：先看 `status`，失败再按 `code` 分支
- `401` 统一处理登录态
- `422` 读取 `error` 渲染表单错误

## 4. 排错清单

异常未自动转 JSON 时检查：

1. `FELO_API_ENABLE_EXCEPTION_HANDLER` 是否为 `true`
2. 路径是否命中 `render_api_paths`（注意 Hyperf 路径带前导 `/`）
3. 请求头是否包含 `Accept: application/json`
4. `ApiExceptionHandler` 在异常处理器链中的优先级是否足够高
