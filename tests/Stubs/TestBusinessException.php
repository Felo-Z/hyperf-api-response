<?php

declare(strict_types=1);

namespace Tests\Stubs;

use FeloZ\HyperfApiResponse\Support\Contracts\BusinessThrowable;
use RuntimeException;

class TestBusinessException extends RuntimeException implements BusinessThrowable
{
    public function __construct(
        string $message,
        protected int $businessCode,
        protected ?array $errorData = null
    ) {
        parent::__construct($message);
    }

    public function getBusinessCode(): int
    {
        return $this->businessCode;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}
