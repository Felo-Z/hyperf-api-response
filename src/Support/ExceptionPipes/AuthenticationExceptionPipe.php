<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
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
            'code' => 401,
            'message' => $throwable->getMessage() ?: 'Unauthenticated.',
        ] + $structure;
    }
}
