<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\Contracts\BusinessThrowable;
use Throwable;

/**
 * 业务异常 Pipe。
 *
 * 识别实现了 BusinessThrowable 契约的异常，将其业务码与消息映射到统一响应结构。
 * 业务码会进入响应体的 code 字段，HTTP 状态码由 http_status 字段控制（默认 400）。
 */
class BusinessExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        if (! $throwable instanceof BusinessThrowable) {
            return $structure;
        }

        return [
            'code' => $throwable->getBusinessCode(),
            'message' => $throwable->getMessage(),
            'error' => $throwable->getErrorData(),
            'http_status' => 400,
        ] + $structure;
    }
}
