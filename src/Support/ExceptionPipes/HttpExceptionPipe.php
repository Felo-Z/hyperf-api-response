<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\ApiCode;
use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;

class HttpExceptionPipe
{
    /**
     * HTTP 状态码 → 业务码映射。未列出的统一映射为 BIZ_FAILED。
     */
    private const STATUS_TO_BIZ_CODE = [
        400 => ApiCode::BIZ_FAILED,
        401 => ApiCode::BIZ_UNAUTHORIZED,
        403 => ApiCode::BIZ_FORBIDDEN,
        404 => ApiCode::BIZ_NOT_FOUND,
        409 => ApiCode::BIZ_CONFLICT,
        422 => ApiCode::BIZ_VALIDATION_ERROR,
        429 => ApiCode::BIZ_TOO_MANY_REQUESTS,
    ];

    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        if (! $throwable instanceof HttpException) {
            return $structure;
        }

        $httpStatus = $throwable->getStatusCode();
        $bizCode = self::STATUS_TO_BIZ_CODE[$httpStatus] ?? ApiCode::BIZ_FAILED;

        return [
            'code' => $bizCode,
            'message' => $throwable->getMessage(),
            'http_status' => $httpStatus,
        ] + $structure;
    }
}
