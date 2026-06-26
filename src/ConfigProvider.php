<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse;

use FeloZ\HyperfApiResponse\Exception\Handler\ApiExceptionHandler;
use FeloZ\HyperfApiResponse\Support\ApiResponse;
use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ApiResponseContract::class => ApiResponse::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        ApiExceptionHandler::class,
                    ],
                ],
            ],
            'api-response' => require __DIR__ . '/publish/api-response.php',
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for api-response.',
                    'source' => __DIR__ . '/publish/api-response.php',
                    'destination' => BASE_PATH . '/config/autoload/api-response.php',
                ],
            ],
        ];
    }
}
