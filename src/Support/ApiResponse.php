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

/**
 * 统一 API 响应构造器。
 *
 * 设计契约（code 与 HTTP 状态码彻底解耦）：
 *   - 响应体 code 永远是业务码：0 = 成功，非 0 = 业务/框架错误码
 *   - status = (code === 0)，由同一处生成，保证不变式
 *   - HTTP 状态码独立存在于传输层（http_status），不进入响应体
 *
 * 业务码命名空间：
 *   - 包级 ApiCode：1000-1999（框架层通用错误）
 *   - 项目自定义码：建议使用 1000-1999 以外的整数（如按域分段 200404），运行时无校验
 */
class ApiResponse implements ApiResponseContract
{
    use Macroable;

    public function __construct(
        protected ResponseInterface $response
    ) {}

    // ============================================================
    // 成功响应（body code 恒为 ApiCode::BIZ_OK = 0）
    // ============================================================

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

    /**
     * 成功响应。body code 恒为 0，httpStatus 控制传输层状态码。
     */
    public function success(mixed $data = null, string $message = '', int $httpStatus = 200): PsrResponseInterface
    {
        return $this->buildJson(ApiCode::BIZ_OK, $message, $data, null, $httpStatus);
    }

    /**
     * 文案优先的成功响应。body code 恒为 0，HTTP 由 $httpStatus 控制。
     */
    public function message(string $message, mixed $data = null, int $httpStatus = 200): PsrResponseInterface
    {
        return $this->buildJson(ApiCode::BIZ_OK, $message, $data, null, $httpStatus);
    }

    // ============================================================
    // 失败响应（body code 为业务码，httpStatus 独立）
    // ============================================================

    /**
     * 失败响应。
     *
     * @param int $code 业务码（来自 ApiCode 或业务项目 ErrorCode）
     * @param int $httpStatus 传输层 HTTP 状态码
     */
    public function failed(string $message = '', int $code = ApiCode::BIZ_FAILED, int $httpStatus = 400, ?array $error = null): PsrResponseInterface
    {
        if ($code === ApiCode::BIZ_OK) {
            $code = ApiCode::BIZ_FAILED;
        }

        return $this->buildJson($code, $message, null, $error, $httpStatus);
    }

    public function error(string $message = '', int $code = ApiCode::BIZ_FAILED, int $httpStatus = 400, ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, $code, $httpStatus, $error);
    }

    public function badRequest(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 400, $error);
    }

    public function unauthorized(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_UNAUTHORIZED, 401, $error);
    }

    public function paymentRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 402, $error);
    }

    public function forbidden(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FORBIDDEN, 403, $error);
    }

    public function notFound(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_NOT_FOUND, 404, $error);
    }

    public function methodNotAllowed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 405, $error);
    }

    public function notAcceptable(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 406, $error);
    }

    public function proxyAuthenticationRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_UNAUTHORIZED, 407, $error);
    }

    public function requestTimeout(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 408, $error);
    }

    public function conflict(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_CONFLICT, 409, $error);
    }

    public function gone(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 410, $error);
    }

    public function lengthRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 411, $error);
    }

    public function preconditionFailed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 412, $error);
    }

    public function requestEntityTooLarge(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 413, $error);
    }

    public function requestUriTooLong(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 414, $error);
    }

    public function unsupportedMediaType(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 415, $error);
    }

    public function requestedRangeNotSatisfiable(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 416, $error);
    }

    public function expectationFailed(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 417, $error);
    }

    public function iAmATeapot(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 418, $error);
    }

    public function misdirectedRequest(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 421, $error);
    }

    public function unprocessableEntity(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_VALIDATION_ERROR, 422, $error);
    }

    public function locked(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 423, $error);
    }

    public function failedDependency(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 424, $error);
    }

    public function tooEarly(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 425, $error);
    }

    public function upgradeRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 426, $error);
    }

    public function preconditionRequired(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 428, $error);
    }

    public function tooManyRequests(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_TOO_MANY_REQUESTS, 429, $error);
    }

    public function requestHeaderFieldsTooLarge(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 431, $error);
    }

    public function unavailableForLegalReasons(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_FAILED, 451, $error);
    }

    public function internalServerError(string $message = '', ?array $error = null): PsrResponseInterface
    {
        return $this->failed($message, ApiCode::BIZ_SYSTEM_ERROR, 500, $error);
    }

    public function debug(mixed $payload = null, string $message = '', int $httpStatus = 500): PsrResponseInterface
    {
        if ($payload instanceof Throwable) {
            return $this->exception($payload);
        }

        $error = app_debug() ? [
            'debug' => $payload,
        ] : null;

        return $this->failed($message ?: 'Debug Failed', ApiCode::BIZ_SYSTEM_ERROR, $httpStatus, $error);
    }

    // ============================================================
    // 异常转响应
    // ============================================================

    public function exception(Throwable $throwable): PsrResponseInterface
    {
        $structure = $this->newPipeline()
            ->send($throwable)
            ->through($this->exceptionPipes())
            ->then($this->exceptionDestination());

        $response = $this->buildJson(
            (int) ($structure['code'] ?? ApiCode::BIZ_SYSTEM_ERROR),
            (string) ($structure['message'] ?? ''),
            null,
            $structure['error'] ?? null,
            (int) ($structure['http_status'] ?? 500)
        );

        return $this->withHeaders($response, (array) ($structure['headers'] ?? []));
    }

    // ============================================================
    // 核心：构建响应
    // ============================================================

    /**
     * @param int $code 业务码（0 = 成功）
     * @param int|null $httpStatus 传输层 HTTP 状态码，null 时按成败取 200/400
     */
    public function json(
        int $code,
        string $message = '',
        mixed $data = null,
        ?array $error = null,
        ?int $httpStatus = null
    ): PsrResponseInterface {
        return $this->buildJson($code, $message, $data, $error, $httpStatus);
    }

    /**
     * 构建 JSON 响应。status 由 code 推导，保证 status === (code === ApiCode::BIZ_OK)。
     */
    protected function buildJson(
        int $code,
        string $message = '',
        mixed $data = null,
        ?array $error = null,
        ?int $httpStatus = null
    ): PsrResponseInterface {
        $status = $this->resolveStatus($code);
        $httpStatus ??= ($status ? 200 : 400);

        return $this->newPipeline()
            ->send([
                'status' => $status,
                'code' => $code,
                'message' => $message,
                'data' => $data,
                'error' => $error,
                'http_status' => $httpStatus,
            ])
            ->through($this->pipes())
            ->then($this->destination());
    }

    protected function resolveStatus(int $code): bool
    {
        return $code === ApiCode::BIZ_OK;
    }

    protected function resolveHttpStatus(array $structure, bool $status): int
    {
        if (isset($structure['http_status'])) {
            return (int) $structure['http_status'];
        }

        $fallbackSuccess = (int) config('api-response.fallback_success_status_code', 200);
        $fallbackError = (int) config('api-response.fallback_error_status_code', 400);

        return $status ? $fallbackSuccess : $fallbackError;
    }

    /**
     * RFC 7231：204 / 205 响应不得包含 message body。
     */
    protected function isEmptyBodyStatus(int $httpStatus): bool
    {
        return in_array($httpStatus, [204, 205], true);
    }

    protected function newPipeline(): Pipeline
    {
        return new Pipeline(ApplicationContext::getContainer());
    }

    protected function pipes(): array
    {
        return (array) config('api-response.pipes', []);
    }

    protected function exceptionPipes(): array
    {
        return (array) config('api-response.exception_pipes', []);
    }

    /**
     * 异常兜底：未被任何 ExceptionPipe 匹配的异常视为系统错误。
     */
    protected function exceptionDestination(): \Closure
    {
        return static function (Throwable $throwable): array {
            $debug = app_debug();

            return [
                'code' => ApiCode::BIZ_SYSTEM_ERROR,
                'http_status' => 500,
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

    /**
     * 终点：构建响应体（仅包含 status/code/message/data/error），并应用 http_status。
     */
    protected function destination(): \Closure
    {
        return function (array $structure): PsrResponseInterface {
            $code = (int) $structure['code'];
            $status = $this->resolveStatus($code);
            $httpStatus = $this->resolveHttpStatus($structure, $status);

            if ($this->isEmptyBodyStatus($httpStatus)) {
                return $this->response
                    ->withBody(new SwooleStream(''))
                    ->withStatus($httpStatus);
            }

            $body = [
                'status' => $status,
                'code' => $code,
                'message' => (string) $structure['message'],
                'data' => $structure['data'] ?? null,
            ];

            // error 字段是否输出由 ErrorPipe 决定（隐藏时已 unset）
            if (array_key_exists('error', $structure)) {
                $body['error'] = $structure['error'];
            }

            $options = JSON_UNESCAPED_UNICODE;
            if (! $status && app_debug()) {
                $options |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
            }

            $json = json_encode($body, $options);
            if ($json === false) {
                return $this->responseFromJsonEncodeFailure();
            }

            return $this->response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withBody(new SwooleStream($json))
                ->withStatus($httpStatus);
        };
    }

    protected function responseFromJsonEncodeFailure(): PsrResponseInterface
    {
        $debug = app_debug();
        $encodeError = json_last_error_msg() ?: 'Unknown JSON encode error';
        $body = [
            'status' => false,
            'code' => ApiCode::BIZ_SYSTEM_ERROR,
            'message' => $debug ? $encodeError : 'Internal Server Error',
            'data' => null,
        ];

        if ($debug) {
            $body['error'] = [
                'type' => 'json_encode_error',
                'message' => $encodeError,
            ];
        }

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{"status":false,"code":1999,"message":"Internal Server Error","data":null}';
        }

        return $this->response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream($json))
            ->withStatus(500);
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
