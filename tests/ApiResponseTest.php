<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use FeloZ\HyperfApiResponse\Support\ApiResponse;
use FeloZ\HyperfApiResponse\Support\RequestClassifier;
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
        $this->assertSame(200, $data['code']);
        $this->assertSame('done', $data['message']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function test_message_response(): void
    {
        $response = ap()->message('created', 201, ['name' => 'demo']);
        $data = $this->decodeResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['status']);
        $this->assertSame('created', $data['message']);
    }

    public function test_created_response_with_location_header(): void
    {
        $response = ap()->created(['id' => 1], 'created', '/api/users/1');
        $data = $this->decodeResponse($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['status']);
        $this->assertSame('created', $data['message']);
        $this->assertSame('/api/users/1', $response->getHeaderLine('Location'));
    }

    public function test_failed_response(): void
    {
        $response = ap()->failed('bad request', 400, ['field' => 'name']);
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame('bad request', $data['message']);
        $this->assertSame(['field' => 'name'], $data['error']);
    }

    public function test_error_is_alias_of_failed(): void
    {
        $response = ap()->error('bad', 400, ['k' => 'v']);
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame('bad', $data['message']);
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

    public function test_exception_response_from_http_exception_pipe(): void
    {
        $response = ap()->exception(new HttpException(404, 'not found'));
        $data = $this->decodeResponse($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame('not found', $data['message']);
    }

    public function test_debug_with_throwable_uses_exception_flow(): void
    {
        $response = ap()->debug(new Exception('boom'));
        $data = $this->decodeResponse($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame('boom', $data['message']);
        $this->assertIsArray($data['error']);
        $this->assertSame(Exception::class, $data['error']['type']);
    }

    public function test_project_can_extend_api_response_by_macro(): void
    {
        ApiResponse::macro('userNotFound', function () {
            /** @var ApiResponse $this */
            return $this->failed('用户不存在', 200404, ['type' => 'biz_error']);
        });

        $response = ap()->userNotFound();
        $data = $this->decodeResponse($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['status']);
        $this->assertSame(200404, $data['code']);
        $this->assertSame('用户不存在', $data['message']);
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
