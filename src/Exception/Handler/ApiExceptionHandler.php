<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Exception\Handler;

use FeloZ\HyperfApiResponse\Support\RequestClassifier;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function api_response;

class ApiExceptionHandler extends ExceptionHandler
{
    public function __construct(
        protected RequestClassifier $classifier
    ) {}

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        return api_response()->exception($throwable);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $this->classifier->shouldHandleAsApi();
    }
}
