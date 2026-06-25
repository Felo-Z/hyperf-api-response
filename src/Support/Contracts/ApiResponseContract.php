<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Contracts;

use FeloZ\HyperfApiResponse\Support\ApiCode;
use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ApiResponseContract
{
    public function ok(mixed $data = null, string $message = ''): ResponseInterface;

    public function created(mixed $data = null, string $message = '', ?string $location = null): ResponseInterface;

    public function accepted(mixed $data = null, string $message = ''): ResponseInterface;

    public function success(mixed $data = null, string $message = '', int $httpStatus = 200): ResponseInterface;

    public function message(string $message, mixed $data = null, int $httpStatus = 200): ResponseInterface;

    public function failed(string $message = '', int $code = ApiCode::BIZ_FAILED, int $httpStatus = 400, ?array $error = null): ResponseInterface;

    public function error(string $message = '', int $code = ApiCode::BIZ_FAILED, int $httpStatus = 400, ?array $error = null): ResponseInterface;

    public function badRequest(string $message = '', ?array $error = null): ResponseInterface;

    public function unauthorized(string $message = '', ?array $error = null): ResponseInterface;

    public function forbidden(string $message = '', ?array $error = null): ResponseInterface;

    public function notFound(string $message = '', ?array $error = null): ResponseInterface;

    public function unprocessableEntity(string $message = '', ?array $error = null): ResponseInterface;

    public function tooManyRequests(string $message = '', ?array $error = null): ResponseInterface;

    public function internalServerError(string $message = '', ?array $error = null): ResponseInterface;

    public function debug(mixed $payload = null, string $message = '', int $httpStatus = 500): ResponseInterface;

    public function exception(Throwable $throwable): ResponseInterface;

    public function json(
        int $code,
        string $message = '',
        mixed $data = null,
        ?array $error = null,
        ?int $httpStatus = null
    ): ResponseInterface;
}
