<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Contracts;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ApiResponseContract
{
    public function ok(mixed $data = null, string $message = ''): ResponseInterface;

    public function created(mixed $data = null, string $message = '', ?string $location = null): ResponseInterface;

    public function accepted(mixed $data = null, string $message = ''): ResponseInterface;

    public function success(mixed $data = null, string $message = '', int $code = 200): ResponseInterface;

    public function message(string $message, int $code = 200, mixed $data = null): ResponseInterface;

    public function failed(string $message = '', int $code = 400, ?array $error = null): ResponseInterface;

    public function error(string $message = '', int $code = 400, ?array $error = null): ResponseInterface;

    public function badRequest(string $message = '', ?array $error = null): ResponseInterface;

    public function unauthorized(string $message = '', ?array $error = null): ResponseInterface;

    public function forbidden(string $message = '', ?array $error = null): ResponseInterface;

    public function notFound(string $message = '', ?array $error = null): ResponseInterface;

    public function unprocessableEntity(string $message = '', ?array $error = null): ResponseInterface;

    public function tooManyRequests(string $message = '', ?array $error = null): ResponseInterface;

    public function internalServerError(string $message = '', ?array $error = null): ResponseInterface;

    public function debug(mixed $payload = null, string $message = '', int $code = 500): ResponseInterface;

    public function exception(Throwable $throwable): ResponseInterface;

    public function json(
        bool|int|string $status,
        int $code,
        string $message = '',
        mixed $data = null,
        ?array $error = null
    ): ResponseInterface;
}
