<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Pipes;

use Closure;
use FeloZ\HyperfApiResponse\Support\Trace\TraceContext;
use Psr\Http\Message\ResponseInterface;

class TracePipe
{
    public function handle(array $structure, Closure $next): ResponseInterface
    {
        if (TraceContext::isEnabled()) {
            $structure['trace_id'] = TraceContext::traceId();
            $spanId = TraceContext::spanId();
            if ($spanId !== null) {
                $structure['span_id'] = $spanId;
            }
            $structure['trace_log'] = TraceContext::logs();
        }

        return $next($structure);
    }
}
