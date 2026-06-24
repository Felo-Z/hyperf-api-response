<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;

class HttpExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        if (! $throwable instanceof HttpException) {
            return $structure;
        }

        return [
            'code' => $throwable->getStatusCode(),
            'message' => $throwable->getMessage(),
        ] + $structure;
    }
}
