<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Trace;

use Hyperf\Context\Context;

final class TraceContext
{
    private const KEY = 'api-response.trace';

    public static function start(string $traceId, ?string $spanId = null): void
    {
        Context::set(self::KEY, [
            'enabled' => true,
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'logs' => [],
        ]);
    }

    public static function isEnabled(): bool
    {
        $state = Context::get(self::KEY);

        return is_array($state) && ($state['enabled'] ?? false) === true;
    }

    public static function traceId(): ?string
    {
        $state = Context::get(self::KEY);

        if (! is_array($state)) {
            return null;
        }

        $traceId = $state['trace_id'] ?? null;

        return is_string($traceId) && $traceId !== '' ? $traceId : null;
    }

    public static function spanId(): ?string
    {
        $state = Context::get(self::KEY);

        if (! is_array($state)) {
            return null;
        }

        $spanId = $state['span_id'] ?? null;

        return is_string($spanId) && $spanId !== '' ? $spanId : null;
    }

    /**
     * @return list<array{t: string, msg: string, ctx: array}>
     */
    public static function logs(): array
    {
        $state = Context::get(self::KEY);

        if (! is_array($state)) {
            return [];
        }

        return $state['logs'] ?? [];
    }

    /**
     * @param array{t: string, msg: string, ctx: array} $entry
     */
    public static function append(array $entry): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $state = Context::get(self::KEY);
        if (! is_array($state)) {
            return;
        }

        $logs = $state['logs'] ?? [];
        $logs[] = $entry;
        $state['logs'] = $logs;
        Context::set(self::KEY, $state);
    }

    public static function clear(): void
    {
        Context::destroy(self::KEY);
    }
}
