<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

class HttpStatusTexts
{
    private const TEXTS = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
    ];

    public static function get(int $code): ?string
    {
        return self::TEXTS[$code] ?? null;
    }
}
