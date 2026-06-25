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
        $hideSystemError = ! app_debug()
            && (bool) config('felo-api-response.api_response.hide_error_when_not_debug', true);

        if (array_key_exists('error', $structure) && $structure['error'] !== null) {
            if ($hideSystemError && $this->isSystemDiagnosticError((array) $structure['error'])) {
                unset($structure['error']);
            }
        } elseif (app_debug() && (! array_key_exists('error', $structure) || $structure['error'] === null)) {
            $structure['error'] = (object) [];
        }

        return $next($structure);
    }

    /**
     * 系统诊断类 error（堆栈/编码失败等）生产环境隐藏；校验、业务 error 保留。
     */
    private function isSystemDiagnosticError(array $error): bool
    {
        if (($error['type'] ?? null) === 'json_encode_error') {
            return true;
        }

        if (array_key_exists('trace', $error)) {
            return true;
        }

        return isset($error['type'], $error['message'], $error['file'], $error['line']);
    }
}
