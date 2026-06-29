<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Trace;

use Psr\Http\Message\ServerRequestInterface;

use function Hyperf\Config\config;

class TraceParamResolver
{
    public function isTraceRequested(ServerRequestInterface $request): bool
    {
        $param = (string) config('api-response.trace.param', 'trace');
        $queryParams = $request->getQueryParams();

        if ($queryParams === [] && $request->getUri()->getQuery() !== '') {
            parse_str($request->getUri()->getQuery(), $queryParams);
        }

        if (array_key_exists($param, $queryParams)) {
            return $this->isTruthy($queryParams[$param]);
        }

        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && array_key_exists($param, $parsedBody)) {
            return $this->isTruthy($parsedBody[$param]);
        }

        return false;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filtered !== null) {
                return $filtered;
            }

            return $value === '1';
        }

        return false;
    }
}
