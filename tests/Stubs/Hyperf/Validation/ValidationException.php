<?php

declare(strict_types=1);

namespace Hyperf\Validation;

use RuntimeException;

/**
 * 测试用 ValidationException stub（模拟 hyperf/validation 可选依赖）.
 */
class ValidationException extends RuntimeException
{
    public function __construct(
        protected array $errors = [],
        public int $status = 422,
        string $message = ''
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
