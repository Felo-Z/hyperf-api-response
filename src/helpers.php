<?php

declare(strict_types=1);

use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;
use Hyperf\Context\ApplicationContext;

use function Hyperf\Support\env;

if (! function_exists('app_debug')) {
    function app_debug(): bool
    {
        return filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
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
