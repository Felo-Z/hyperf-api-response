<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use Psr\Http\Message\ResponseInterface;

use function app_debug;
use function Hyperf\Config\config;

class ErrorPipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        $hideError = ! app_debug()
            && (bool) config('felo-api-response.api_response.hide_error_when_not_debug', true);

        if ($hideError) {
            unset($structure['error']);
        } elseif (! array_key_exists('error', $structure) || $structure['error'] === null) {
            $structure['error'] = (object) [];
        }

        return $next($structure);
    }
}
