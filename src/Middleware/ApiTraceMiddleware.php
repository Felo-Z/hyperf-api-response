<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Middleware;

use FeloZ\HyperfApiResponse\Support\Trace\TraceContext;
use FeloZ\HyperfApiResponse\Support\Trace\TraceIdResolver;
use FeloZ\HyperfApiResponse\Support\Trace\TraceParamResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Hyperf\Config\config;

class ApiTraceMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected TraceParamResolver $paramResolver,
        protected TraceIdResolver $traceIdResolver,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ((bool) config('api-response.trace.enabled', true) && $this->paramResolver->isTraceRequested($request)) {
            TraceContext::start(
                $this->traceIdResolver->resolve($request),
                $this->traceIdResolver->resolveSpanId($request),
            );
        }

        return $handler->handle($request);
    }
}
