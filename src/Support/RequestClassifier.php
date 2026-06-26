<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

use Hyperf\Context\Context;
use Hyperf\Stringable\Str;
use Psr\Http\Message\ServerRequestInterface;

use function Hyperf\Config\config;

class RequestClassifier
{
    public function shouldHandleAsApi(): bool
    {
        if (! (bool) config('api-response.enable_exception_handler', true)) {
            return false;
        }

        $request = Context::get(ServerRequestInterface::class);
        if (! $request instanceof ServerRequestInterface) {
            return false;
        }

        if ($this->wantsJson($request)) {
            return true;
        }

        $path = $request->getUri()->getPath();
        foreach ((array) config('api-response.render_api_paths', ['api/*']) as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');

        return str_contains($accept, 'application/json')
            || str_contains($accept, '+json');
    }
}
