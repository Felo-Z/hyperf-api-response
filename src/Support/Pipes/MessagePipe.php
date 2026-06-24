<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\HttpStatusTexts;
use Psr\Http\Message\ResponseInterface;

class MessagePipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        if ($structure['message'] === '') {
            $code = (int) ($structure['code'] ?? 200);
            $structure['message'] = HttpStatusTexts::get($code)
                ?? ($structure['status'] ? 'OK' : 'Error');
        }

        return $next($structure);
    }
}
