<?php

declare(strict_types=1);

use FeloZ\HyperfApiResponse\Support\ApiTrace;
use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;
use Hyperf\Context\ApplicationContext;

use function Hyperf\Config\config;
use function Hyperf\Support\env;

if (! function_exists('app_debug')) {
    /**
     * 读取 API 响应包的 debug 开关。
     *
     * 使用 static 缓存：Swoole Worker 长驻进程内避免重复读 config/env；
     * 缓存 key 由 app_debug_cache_key() 提供，单测中动态改 config 时可自动失效。
     */
    function app_debug(): bool
    {
        static $cached = null;
        static $cacheKey = null;

        $key = app_debug_cache_key();
        if ($cacheKey === $key && $cached !== null) {
            return $cached;
        }

        $cacheKey = $key;
        $cached = filter_var($key, FILTER_VALIDATE_BOOLEAN);

        return $cached;
    }
}

if (! function_exists('app_debug_cache_key')) {
    /**
     * 供 app_debug() 生成缓存 key；config 变更后 key 变化即重新读取。
     *
     * 读取顺序：config('api-response.app_debug') → env 兜底。
     *
     * env 兜底仅用于「启动/bootstrap 阶段」：helpers.php 经 Composer files 极早加载，
     * 此时 ApplicationContext 或 Config 可能尚未就绪（不是指 Hyperf 框架的某个旧版本）。
     * 正常运行时 ConfigProvider 已 merge 配置，应始终走 config 分支。
     *
     * 注意：此 env 兜底适用于 Hyperf/Swoole；Laravel 在 config:cache 后 runtime 不宜调 env()，
     * 故 laravel-api-response 只读 config，不复制此 fallback。
     */
    function app_debug_cache_key(): string
    {
        if (ApplicationContext::hasContainer()) {
            try {
                $value = config('api-response.app_debug');
                if ($value !== null) {
                    return $value ? '1' : '0';
                }
            } catch (\Throwable) {
            }
        }

        return (string) env('API_RESPONSE_APP_DEBUG', env('APP_DEBUG', false));
    }
}

if (! function_exists('api_response')) {
    /**
     * 获取 API 响应构造器实例.
     */
    function api_response(): ApiResponseContract
    {
        return ApplicationContext::getContainer()->get(ApiResponseContract::class);
    }
}

if (! function_exists('api_trace')) {
    /**
     * 追加请求级 trace 日志（需入参 trace=true 且 ApiTraceMiddleware 已启用）.
     */
    function api_trace(string $message, array $context = []): void
    {
        ApplicationContext::getContainer()->get(ApiTrace::class)->trace($message, $context);
    }
}
