<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Trace;

use Psr\Http\Message\ServerRequestInterface;

class TraceIdResolver
{
    public function resolve(ServerRequestInterface $request): string
    {
        $traceId = $this->fromOpenTelemetry()
            ?? $this->fromTraceparentHeader($request)
            ?? $this->generate();

        return strtolower($traceId);
    }

    public function resolveSpanId(ServerRequestInterface $request): ?string
    {
        $spanId = $this->spanIdFromOpenTelemetry()
            ?? $this->spanIdFromTraceparentHeader($request);

        if ($spanId === null || ! $this->isValidSpanId($spanId)) {
            return null;
        }

        return strtolower($spanId);
    }

    private function fromOpenTelemetry(): ?string
    {
        if (! class_exists(\OpenTelemetry\API\Trace\Span::class)) {
            return null;
        }

        try {
            $context = \OpenTelemetry\API\Trace\Span::getCurrent()->getContext();
            if (! $context->isValid()) {
                return null;
            }

            $traceId = (string) $context->getTraceId();

            return $this->isValidTraceId($traceId) ? $traceId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function spanIdFromOpenTelemetry(): ?string
    {
        if (! class_exists(\OpenTelemetry\API\Trace\Span::class)) {
            return null;
        }

        try {
            $context = \OpenTelemetry\API\Trace\Span::getCurrent()->getContext();
            if (! $context->isValid()) {
                return null;
            }

            $spanId = (string) $context->getSpanId();

            return $this->isValidSpanId($spanId) ? $spanId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fromTraceparentHeader(ServerRequestInterface $request): ?string
    {
        $parsed = $this->parseTraceparent($request);

        return $parsed['trace_id'] ?? null;
    }

    private function spanIdFromTraceparentHeader(ServerRequestInterface $request): ?string
    {
        $parsed = $this->parseTraceparent($request);

        return $parsed['span_id'] ?? null;
    }

    /**
     * @return array{trace_id?: string, span_id?: string}
     */
    private function parseTraceparent(ServerRequestInterface $request): array
    {
        $header = trim($request->getHeaderLine('traceparent'));
        if ($header === '') {
            return [];
        }

        $parts = explode('-', $header);
        if (count($parts) !== 4) {
            return [];
        }

        $result = [];
        $traceId = $parts[1];
        $spanId = $parts[2];

        if ($this->isValidTraceId($traceId)) {
            $result['trace_id'] = $traceId;
        }

        if ($this->isValidSpanId($spanId)) {
            $result['span_id'] = $spanId;
        }

        return $result;
    }

    public function generate(): string
    {
        do {
            $traceId = bin2hex(random_bytes(16));
        } while (! $this->isValidTraceId($traceId));

        return $traceId;
    }

    private function isValidTraceId(string $traceId): bool
    {
        if (strlen($traceId) !== 32 || ! ctype_xdigit($traceId)) {
            return false;
        }

        return $traceId !== str_repeat('0', 32);
    }

    private function isValidSpanId(string $spanId): bool
    {
        if (strlen($spanId) !== 16 || ! ctype_xdigit($spanId)) {
            return false;
        }

        return $spanId !== str_repeat('0', 16);
    }
}
