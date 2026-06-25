<?php

declare(strict_types=1);

use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;
use Hyperf\Context\ApplicationContext;

use function Hyperf\Config\config;
use function Hyperf\Support\env;

if (! function_exists('app_debug')) {
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
     * 供 app_debug() 缓存失效：config 变更后 key 变化即重新读取。
     */
    function app_debug_cache_key(): string
    {
        if (ApplicationContext::hasContainer()) {
            try {
                $value = config('felo-api-response.api_response.app_debug');
                if ($value !== null) {
                    return $value ? '1' : '0';
                }
            } catch (\Throwable) {
            }
        }

        return (string) env('APP_DEBUG', false);
    }
}

if (! function_exists('ap')) {
    /**
     * 获取 API 响应构造器实例.
     */
    function ap(): ApiResponseContract
    {
        return ApplicationContext::getContainer()->get(ApiResponseContract::class);
    }
}
