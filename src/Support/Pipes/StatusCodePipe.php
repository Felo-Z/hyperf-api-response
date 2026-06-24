<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use Psr\Http\Message\ResponseInterface;

use function Hyperf\Config\config;

class StatusCodePipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        $response = $next($structure);
        $code = (int) ($structure['code'] ?? 0);

        if ($code >= 100 && $code <= 599) {
            return $response->withStatus($code);
        }

        $fallbackSuccess = (int) config('felo-api-response.api_response.fallback_success_status_code', 200);
        $fallbackError = (int) config('felo-api-response.api_response.fallback_error_status_code', 400);

        return $response->withStatus($structure['status'] ? $fallbackSuccess : $fallbackError);
    }
}
