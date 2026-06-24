<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use Psr\Http\Message\ResponseInterface;

class StatusCodePipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        $response = $next($structure);
        $code = (int) ($structure['code'] ?? 0);

        if ($code >= 100 && $code <= 599) {
            return $response->withStatus($code);
        }

        return $response->withStatus($structure['status'] ? 200 : 400);
    }
}
