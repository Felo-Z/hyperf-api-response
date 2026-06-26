<?php

declare(strict_types=1);

use FeloZ\HyperfApiResponse\Support\ExceptionPipes\AuthenticationExceptionPipe;
use FeloZ\HyperfApiResponse\Support\ExceptionPipes\BusinessExceptionPipe;
use FeloZ\HyperfApiResponse\Support\ExceptionPipes\HttpExceptionPipe;
use FeloZ\HyperfApiResponse\Support\ExceptionPipes\ValidationExceptionPipe;
use FeloZ\HyperfApiResponse\Support\Pipes\ErrorPipe;
use FeloZ\HyperfApiResponse\Support\Pipes\MessagePipe;

use function Hyperf\Support\env;

return [
    'enable_exception_handler' => (bool) env('API_RESPONSE_ENABLE_EXCEPTION_HANDLER', true),
    'render_api_paths' => ['/api/*'],
    'hide_error_when_not_debug' => (bool) env('API_RESPONSE_HIDE_ERROR', true),
    'app_debug' => (bool) env('API_RESPONSE_APP_DEBUG', env('APP_DEBUG', false)),
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
];
