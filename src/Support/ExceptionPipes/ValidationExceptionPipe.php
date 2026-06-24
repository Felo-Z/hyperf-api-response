<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\ExceptionPipes;

use Closure;
use Throwable;

class ValidationExceptionPipe
{
    public function handle(Throwable $throwable, Closure $next): array
    {
        $structure = $next($throwable);

        if (! is_a($throwable, 'Hyperf\\Validation\\ValidationException')) {
            return $structure;
        }

        $errors = $throwable->errors();
        $firstMessage = '';
        foreach ($errors as $messages) {
            if (is_array($messages) && $messages !== []) {
                $firstMessage = (string) reset($messages);
                break;
            }
        }

        return [
            'code' => $throwable->status,
            'message' => $firstMessage,
            'error' => $errors,
        ] + $structure;
    }
}
