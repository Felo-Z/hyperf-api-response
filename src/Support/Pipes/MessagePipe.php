<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\ApiCode;
use FeloZ\HyperfApiResponse\Support\HttpStatusTexts;
use Psr\Http\Message\ResponseInterface;

class MessagePipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        if ($structure['message'] === '') {
            $isSuccess = (int) ($structure['code'] ?? ApiCode::BIZ_FAILED) === ApiCode::BIZ_OK;
            $httpStatus = (int) ($structure['http_status'] ?? ($isSuccess ? 200 : 400));
            $structure['message'] = HttpStatusTexts::get($httpStatus)
                ?? ($isSuccess ? 'OK' : 'Error');
        }

        return $next($structure);
    }
}
