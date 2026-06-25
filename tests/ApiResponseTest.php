<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use FeloZ\HyperfApiResponse\Support\ApiCode;
use FeloZ\HyperfApiResponse\Support\ApiResponse;
use FeloZ\HyperfApiResponse\Support\RequestClassifier;
use Tests\Stubs\TestBusinessException;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Uri\Uri;

class ApiResponseTest extends TestCase
{
    public function test_ok_response(): void
    {
        $response = ap()->ok(['id' => 1], 'done');
        $data = $this->decodeResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['status']);
        $this->assertSame(ApiCode::BIZ_OK, $data['code']);
        $this->assertSame('done', $data['message']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function test_message_response(): void
    {
        $response = ap()->message('created', ['name' => 'demo'], 201);
        $data = $this->decodeResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['status']);
        $this->assertSame(ApiCode::BIZ_OK, $data['code']);
        $this->assertSame('created', $data['message']);
        $this->assertSame(['name' => 'demo'], $data['data']);
    }

    public function test_created_response_with_location_header(): void
    {
        $response = ap()->created(['id' => 1], 'created', '/api/users/1');
        $data = $this->decodeResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['status']);
        $this->assertSame(ApiCode::BIZ_OK, $data['code']);
        $this->assertSame('created', $data['message']);
        $this->assertSame('/api/users/1', $response->getHeaderLine('Location'));
    }

    public function test_failed_response(): void
    {
        $response = ap()->failed('bad request', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_FAILED, $data['code']);
        $this->assertSame('bad request', $data['message']);
        $this->assertSame(['field' => 'name'], $data['error']);
    }

    public function test_error_is_alias_of_failed(): void
    {
        $response = ap()->error('bad', ApiCode::BIZ_FAILED, 400, ['k' => 'v']);
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_FAILED, $data['code']);
        $this->assertSame('bad', $data['message']);
        $this->assertSame(['k' => 'v'], $data['error']);
    }

    public function test_business_code_maps_to_http_400(): void
    {
        $response = ap()->failed('biz fail', 200404);
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(200404, $data['code']);
    }

    public function test_common_error_shortcuts(): void
    {
        $this->assertSame(401, ap()->unauthorized('unauthorized')->getStatusCode());
        $this->assertSame(403, ap()->forbidden('forbidden')->getStatusCode());
        $this->assertSame(404, ap()->notFound('not found')->getStatusCode());
        $this->assertSame(422, ap()->unprocessableEntity('invalid')->getStatusCode());
        $this->assertSame(500, ap()->internalServerError('server error')->getStatusCode());
    }

    public function test_json_encode_failure_returns_system_error_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->ok(['value' => NAN]);
            $data = $this->decodeResponse($response);

            $this->assertSame(500, $response->getStatusCode());
            $this->assertFalse($data['status']);
            $this->assertSame(ApiCode::BIZ_SYSTEM_ERROR, $data['code']);
            $this->assertSame('Internal Server Error', $data['message']);
            $this->assertArrayNotHasKey('error', $data);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_json_encode_failure_includes_detail_when_debug(): void
    {
        $response = ap()->ok(['value' => NAN]);
        $data = $this->decodeResponse($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_SYSTEM_ERROR, $data['code']);
        $this->assertSame('Inf and NaN cannot be JSON encoded', $data['message']);
        $this->assertSame('json_encode_error', $data['error']['type']);
    }

    public function test_no_content_response_has_empty_body(): void
    {
        $response = ap()->noContent();
        $body = (string) $response->getBody();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $body);
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function test_reset_content_response_has_empty_body(): void
    {
        $response = ap()->resetContent(null, '');
        $body = (string) $response->getBody();

        $this->assertSame(205, $response->getStatusCode());
        $this->assertSame('', $body);
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function test_exception_response_from_validation_exception_pipe(): void
    {
        $response = ap()->exception(new \Hyperf\Validation\ValidationException(
            ['email' => ['邮箱格式不正确']],
            422
        ));
        $data = $this->decodeResponse($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_VALIDATION_ERROR, $data['code']);
        $this->assertSame('邮箱格式不正确', $data['message']);
        $this->assertSame(['email' => ['邮箱格式不正确']], $data['error']);
    }

    public function test_validation_exception_keeps_error_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->exception(new \Hyperf\Validation\ValidationException(
                ['email' => ['邮箱格式不正确']],
                422
            ));
            $data = $this->decodeResponse($response);

            $this->assertSame(['email' => ['邮箱格式不正确']], $data['error']);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_exception_response_from_authentication_exception_pipe(): void
    {
        $response = ap()->exception(new \Hyperf\Auth\Exception\UnauthorizedException('Token expired'));
        $data = $this->decodeResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_UNAUTHORIZED, $data['code']);
        $this->assertSame('Token expired', $data['message']);
    }

    public function test_authentication_exception_uses_default_message_when_empty(): void
    {
        $response = ap()->exception(new \Hyperf\Auth\Exception\UnauthorizedException(''));
        $data = $this->decodeResponse($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthenticated.', $data['message']);
    }

    public function test_exception_response_from_business_exception_pipe(): void
    {
        $response = ap()->exception(new TestBusinessException(
            '用户不存在',
            200404,
            ['user_id' => 1]
        ));
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(200404, $data['code']);
        $this->assertSame('用户不存在', $data['message']);
        $this->assertSame(['user_id' => 1], $data['error']);
    }

    public function test_business_exception_without_error_data(): void
    {
        $response = ap()->exception(new TestBusinessException('操作失败', ApiCode::BIZ_FAILED));
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_FAILED, $data['code']);
        $this->assertSame('操作失败', $data['message']);
        $this->assertSame([], $data['error']);
    }

    public function test_plain_exception_is_not_handled_by_business_pipe(): void
    {
        $response = ap()->exception(new Exception('system boom'));
        $data = $this->decodeResponse($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_SYSTEM_ERROR, $data['code']);
        $this->assertSame('system boom', $data['message']);
    }

    public function test_exception_response_from_http_exception_pipe(): void
    {
        $response = ap()->exception(new HttpException(404, 'not found'));
        $data = $this->decodeResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_NOT_FOUND, $data['code']);
        $this->assertSame('not found', $data['message']);
    }

    public function test_debug_with_throwable_uses_exception_flow(): void
    {
        $response = ap()->debug(new Exception('boom'));
        $data = $this->decodeResponse($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_SYSTEM_ERROR, $data['code']);
        $this->assertSame('boom', $data['message']);
        $this->assertIsArray($data['error']);
        $this->assertSame(Exception::class, $data['error']['type']);
    }

    public function test_project_can_extend_api_response_by_macro(): void
    {
        ApiResponse::macro('userNotFound', function () {
            /** @var ApiResponse $this */
            return $this->failed('用户不存在', 200404, 400, ['type' => 'biz_error']);
        });

        $response = ap()->userNotFound();
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(200404, $data['code']);
        $this->assertSame('用户不存在', $data['message']);
    }

    public function test_macros_do_not_leak_between_tests(): void
    {
        $this->assertFalse(ApiResponse::hasMacro('userNotFound'));
    }

    public function test_clear_macros_removes_registered_macro(): void
    {
        ApiResponse::macro('tempMacro', static fn () => null);

        $this->assertTrue(ApiResponse::hasMacro('tempMacro'));

        ApiResponse::clearMacros();

        $this->assertFalse(ApiResponse::hasMacro('tempMacro'));
    }

    public function test_failed_keeps_field_error_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->failed('invalid', ApiCode::BIZ_VALIDATION_ERROR, 422, [
                'email' => ['邮箱格式不正确'],
            ]);
            $data = $this->decodeResponse($response);

            $this->assertSame(['email' => ['邮箱格式不正确']], $data['error']);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_business_exception_keeps_error_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->exception(new TestBusinessException(
                '用户不存在',
                200404,
                ['user_id' => 1]
            ));
            $data = $this->decodeResponse($response);

            $this->assertSame(['user_id' => 1], $data['error']);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_system_diagnostic_error_is_hidden_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->json(ApiCode::BIZ_SYSTEM_ERROR, 'server error', null, [
                'type' => Exception::class,
                'message' => 'boom',
                'file' => '/path/to/file.php',
                'line' => 10,
                'trace' => [],
            ], 500);
            $data = $this->decodeResponse($response);

            $this->assertArrayNotHasKey('error', $data);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_status_is_always_derived_from_code(): void
    {
        // 即使 json() 传入矛盾的 status，输出仍由 code 决定
        $response = ap()->json(ApiCode::BIZ_FAILED, 'conflict');
        $data = $this->decodeResponse($response);

        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_FAILED, $data['code']);
    }

    public function test_failed_normalizes_biz_ok_code(): void
    {
        $response = ap()->failed('should not succeed', ApiCode::BIZ_OK);
        $data = $this->decodeResponse($response);

        $this->assertFalse($data['status']);
        $this->assertSame(ApiCode::BIZ_FAILED, $data['code']);
    }

    public function test_message_pipe_fills_default_from_http_status(): void
    {
        $response = ap()->notFound('');
        $data = $this->decodeResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $data['message']);
    }

    public function test_failed_response_is_compact_when_not_debug(): void
    {
        $this->setAppDebug(false);

        try {
            $response = ap()->failed('bad request', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
            $body = (string) $response->getBody();

            $this->assertStringNotContainsString("\n", $body);
        } finally {
            $this->setAppDebug(true);
        }
    }

    public function test_failed_response_is_pretty_when_debug(): void
    {
        $response = ap()->failed('bad request', ApiCode::BIZ_FAILED, 400, ['field' => 'name']);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("\n", $body);
    }

    public function test_request_classifier_matches_api_path(): void
    {
        $request = (new Request('GET', new Uri('http://localhost/api/users')))
            ->withHeader('Accept', 'text/html');
        Context::set(\Psr\Http\Message\ServerRequestInterface::class, $request);

        $classifier = new RequestClassifier();

        $this->assertTrue($classifier->shouldHandleAsApi());
    }

    public function test_request_classifier_matches_json_accept(): void
    {
        $request = (new Request('GET', new Uri('http://localhost/web/page')))
            ->withHeader('Accept', 'application/json');
        Context::set(\Psr\Http\Message\ServerRequestInterface::class, $request);

        $classifier = new RequestClassifier();

        $this->assertTrue($classifier->shouldHandleAsApi());
    }

    public function test_request_classifier_skips_non_api_request(): void
    {
        $request = (new Request('GET', new Uri('http://localhost/web/page')))
            ->withHeader('Accept', 'text/html');
        Context::set(\Psr\Http\Message\ServerRequestInterface::class, $request);

        $classifier = new RequestClassifier();

        $this->assertFalse($classifier->shouldHandleAsApi());
    }
}
