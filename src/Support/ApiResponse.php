<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

use FeloZ\HyperfApiResponse\Support\Concerns\Macroable;
use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Pipeline\Pipeline;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Throwable;

use function app_debug;
use function Hyperf\Config\config;

class ApiResponse implements ApiResponseContract
{
    use Macroable;

    public function __construct(
        protected ResponseInterface $response
    ) {}

    public function ok(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 200);
    }

    public function created(mixed $data = null, string $message = '', ?string $location = null): PsrResponseInterface
    {
        $response = $this->success($data, $message, 201);
        if ($location) {
            $response = $response->withHeader('Location', $location);
        }

        return $response;
    }

    public function accepted(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 202);
    }

    public function nonAuthoritativeInformation(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 203);
    }

    public function noContent(string $message = ''): PsrResponseInterface
    {
        return $this->success(null, $message, 204);
    }

    public function resetContent(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 205);
    }

    public function partialContent(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 206);
    }

    public function multiStatus(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 207);
    }

    public function alreadyReported(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 208);
    }

    public function imUsed(mixed $data = null, string $message = ''): PsrResponseInterface
    {
        return $this->success($data, $message, 226);
    }

    public function success(mixed $data = null, string $message = '', int $code = 200): PsrResponseInterface
    {
        return $this->json(true, $code, $message, $data);
    }

    public function message(string $message, int $code = 200, mixed $data = null): PsrResponseInterface
    {
        return $this->success($data, $message, $code);
    }

    public function failed(string $message = '', int $code = 400, ?array $error = null): PsrResponseInterface
    {
        return $this->json(false, $code, $message, null, $error);
    }

    public function error(string $message = '', int $code = 400, ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, $code, $error);
    }

    public function badRequest(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 400, $error);
    }

    public function unauthorized(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 401, $error);
    }

    public function paymentRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 402, $error);
    }

    public function forbidden(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 403, $error);
    }

    public function notFound(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 404, $error);
    }

    public function methodNotAllowed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 405, $error);
    }

    public function notAcceptable(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 406, $error);
    }

    public function proxyAuthenticationRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 407, $error);
    }

    public function requestTimeout(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 408, $error);
    }

    public function conflict(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 409, $error);
    }

    public function gone(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 410, $error);
    }

    public function lengthRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 411, $error);
    }

    public function preconditionFailed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 412, $error);
    }

    public function requestEntityTooLarge(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 413, $error);
    }

    public function requestUriTooLong(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 414, $error);
    }

    public function unsupportedMediaType(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 415, $error);
    }

    public function requestedRangeNotSatisfiable(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 416, $error);
    }

    public function expectationFailed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 417, $error);
    }

    public function iAmATeapot(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 418, $error);
    }

    public function misdirectedRequest(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 421, $error);
    }

    public function unprocessableEntity(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 422, $error);
    }

    public function locked(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 423, $error);
    }

    public function failedDependency(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 424, $error);
    }

    public function tooEarly(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 425, $error);
    }

    public function upgradeRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 426, $error);
    }

    public function preconditionRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 428, $error);
    }

    public function tooManyRequests(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 429, $error);
    }

    public function requestHeaderFieldsTooLarge(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 431, $error);
    }

    public function unavailableForLegalReasons(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 451, $error);
    }

    public function internalServerError(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, 500, $error);
    }

    public function debug(mixed $payload = null, string $message = '', int $code = 500): PsrResponseInterface
    {
        if ($payload instanceof Throwable) {
            return $this->exception($payload);
        }

        $error = app_debug() ? [
            'debug' => $payload,
        ] : null;

        return $this->failed($message ?: 'Debug Failed', $code, $error);
    }

    public function exception(Throwable $throwable): PsrResponseInterface
    {
        $structure = $this->newPipeline()
            ->send($throwable)
            ->through($this->exceptionPipes())
            ->then($this->exceptionDestination());

        $response = $this->failed(
            (string) ($structure['message'] ?? ''),
            (int) ($structure['code'] ?? 500),
            $structure['error'] ?? null
        );

        return $this->withHeaders($response, (array) ($structure['headers'] ?? []));
    }

    public function json(
        bool|int|string $status,
        int $code,
        string $message = '',
        mixed $data = null,
        ?array $error = null
    ): PsrResponseInterface {
        return $this->newPipeline()
            ->send([
                'status' => (bool) $status,
                'code' => $code,
                'message' => $message,
                'data' => $data,
                'error' => $error,
            ])
            ->through($this->pipes())
            ->then($this->destination());
    }

    protected function newPipeline(): Pipeline
    {
        return new Pipeline(ApplicationContext::getContainer());
    }

    protected function pipes(): array
    {
        return (array) config('felo-api-response.api_response.pipes', []);
    }

    protected function exceptionPipes(): array
    {
        return (array) config('felo-api-response.api_response.exception_pipes', []);
    }

    protected function exceptionDestination(): \Closure
    {
        return static function (Throwable $throwable): array {
            $code = $throwable->getCode();
            $errorCode = is_int($code) && $code >= 100 && $code <= 599
                ? $code
                : 500;

            $debug = app_debug();

            return [
                'code' => $errorCode,
                'message' => $debug ? $throwable->getMessage() : '',
                'error' => $debug ? [
                    'type' => $throwable::class,
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => $throwable->getTrace(),
                ] : null,
                'headers' => [],
            ];
        };
    }

    protected function destination(): \Closure
    {
        return function (array $structure): PsrResponseInterface {
            $options = JSON_UNESCAPED_UNICODE;
            if (! $structure['status']) {
                $options |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
            }

            $body = json_encode($structure, $options);

            return $this->response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withBody(new SwooleStream((string) $body))
                ->withStatus(200);
        };
    }

    protected function withHeaders(PsrResponseInterface $response, array $headers): PsrResponseInterface
    {
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $response = $response->withAddedHeader((string) $name, (string) $item);
                }
                continue;
            }

            $response = $response->withHeader((string) $name, (string) $value);
        }

        return $response;
    }
}
