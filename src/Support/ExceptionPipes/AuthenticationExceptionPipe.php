<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\ApiCode;
use Throwable;

class AuthenticationExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        $authExceptionClass = 'Hyperf\\Auth\\Exception\\UnauthorizedException';
        if (! is_a($throwable, $authExceptionClass)) {
            return $structure;
        }

        return [
            'code' => ApiCode::BIZ_UNAUTHORIZED,
            'message' => $throwable->getMessage() ?: 'Unauthenticated.',
            'http_status' => 401,
        ] + $structure;
    }
}
