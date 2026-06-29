<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

use FeloZ\HyperfApiResponse\Support\Trace\TraceContext;

use function Hyperf\Config\config;

class ApiTrace
{
    public function isEnabled(): bool
    {
        return TraceContext::isEnabled();
    }

    public function trace(string $message, array $context = []): void
    {
        if (! (bool) config('api-response.trace.enabled', true)) {
            return;
        }

        if (! TraceContext::isEnabled()) {
            return;
        }

        $message = trim($message);
        if ($message === '') {
            return;
        }

        $maxEntries = (int) config('api-response.trace.max_entries', 100);
        if (count(TraceContext::logs()) >= $maxEntries) {
            return;
        }

        TraceContext::append([
            't' => $this->formatTimestamp(),
            'msg' => $message,
            'ctx' => $context,
        ]);
    }

    private function formatTimestamp(): string
    {
        $microtime = microtime(true);
        $seconds = (int) $microtime;
        $milliseconds = (int) round(($microtime - $seconds) * 1000);

        return date('Y-m-d H:i:s', $seconds) . '.' . str_pad((string) $milliseconds, 3, '0', STR_PAD_LEFT);
    }
}
