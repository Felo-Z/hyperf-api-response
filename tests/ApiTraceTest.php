<?php

declare(strict_types=1);

namespace Tests;

use FeloZ\HyperfApiResponse\Middleware\ApiTraceMiddleware;
use FeloZ\HyperfApiResponse\Support\ApiCode;
use FeloZ\HyperfApiResponse\Support\Trace\TraceContext;
use FeloZ\HyperfApiResponse\Support\Trace\TraceIdResolver;
use FeloZ\HyperfApiResponse\Support\Trace\TraceParamResolver;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Uri\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiTraceTest extends TestCase
{
    private const TRACE_ID = '4bf92f3577b34da6a3ce929d0e0e4736';

    private const SPAN_ID = '00f067aa0ba902b7';

    public function test_response_without_trace_has_no_trace_fields(): void
    {
        $response = api_response()->ok(['id' => 1]);
        $data = $this->decodeResponse($response);

        $this->assertArrayNotHasKey('trace_id', $data);
        $this->assertArrayNotHasKey('span_id', $data);
        $this->assertArrayNotHasKey('trace_log', $data);
    }

    public function test_trace_enabled_response_includes_trace_id_and_empty_logs(): void
    {
        TraceContext::start(self::TRACE_ID, self::SPAN_ID);

        $response = api_response()->ok(['id' => 1]);
        $data = $this->decodeResponse($response);

        $this->assertSame(self::TRACE_ID, $data['trace_id']);
        $this->assertSame(self::SPAN_ID, $data['span_id']);
        $this->assertSame([], $data['trace_log']);
    }

    public function test_api_trace_appends_ordered_logs_to_response(): void
    {
        TraceContext::start(self::TRACE_ID);

        api_trace('first', ['step' => 1]);
        api_trace('second', ['step' => 2]);

        $data = $this->decodeResponse(api_response()->ok());

        $this->assertCount(2, $data['trace_log']);
        $this->assertSame('first', $data['trace_log'][0]['msg']);
        $this->assertSame(['step' => 1], $data['trace_log'][0]['ctx']);
        $this->assertSame('second', $data['trace_log'][1]['msg']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/', $data['trace_log'][0]['t']);
    }

    public function test_api_trace_is_noop_when_trace_not_enabled(): void
    {
        api_trace('ignored');

        $data = $this->decodeResponse(api_response()->ok());

        $this->assertArrayNotHasKey('trace_log', $data);
    }

    public function test_failed_response_includes_trace_log(): void
    {
        TraceContext::start(self::TRACE_ID);
        api_trace('before fail');

        $data = $this->decodeResponse(api_response()->failed('bad', ApiCode::BIZ_FAILED, 400, ['field' => 'name']));

        $this->assertFalse($data['status']);
        $this->assertSame(self::TRACE_ID, $data['trace_id']);
        $this->assertSame('before fail', $data['trace_log'][0]['msg']);
    }

    public function test_api_trace_ignores_blank_message(): void
    {
        TraceContext::start(self::TRACE_ID);

        api_trace('   ');

        $data = $this->decodeResponse(api_response()->ok());

        $this->assertSame([], $data['trace_log']);
    }

    public function test_api_trace_respects_max_entries(): void
    {
        $this->container->get(\Hyperf\Contract\ConfigInterface::class)->set('api-response.trace.max_entries', 2);
        TraceContext::start(self::TRACE_ID);

        api_trace('one');
        api_trace('two');
        api_trace('three');

        $data = $this->decodeResponse(api_response()->ok());

        $this->assertCount(2, $data['trace_log']);
        $this->assertSame('one', $data['trace_log'][0]['msg']);
        $this->assertSame('two', $data['trace_log'][1]['msg']);
    }

    public function test_trace_param_resolver_accepts_query_true(): void
    {
        $resolver = new TraceParamResolver();
        $request = new Request('GET', new Uri('http://localhost/api/users?trace=true'));

        $this->assertTrue($resolver->isTraceRequested($request));
    }

    public function test_trace_param_resolver_accepts_body_true(): void
    {
        $resolver = new TraceParamResolver();
        $request = (new Request('POST', new Uri('http://localhost/api/users')))
            ->withParsedBody(['trace' => true, 'name' => 'foo']);

        $this->assertTrue($resolver->isTraceRequested($request));
    }

    public function test_trace_param_resolver_rejects_false_values(): void
    {
        $resolver = new TraceParamResolver();
        $request = new Request('GET', new Uri('http://localhost/api/users?trace=false'));

        $this->assertFalse($resolver->isTraceRequested($request));
    }

    public function test_trace_id_resolver_parses_traceparent_header(): void
    {
        $resolver = new TraceIdResolver();
        $request = (new Request('GET', new Uri('http://localhost/api/users')))
            ->withHeader('traceparent', '00-' . self::TRACE_ID . '-' . self::SPAN_ID . '-01');

        $this->assertSame(self::TRACE_ID, $resolver->resolve($request));
        $this->assertSame(self::SPAN_ID, $resolver->resolveSpanId($request));
    }

    public function test_trace_id_resolver_generates_valid_trace_id(): void
    {
        $resolver = new TraceIdResolver();
        $request = new Request('GET', new Uri('http://localhost/api/users'));

        $traceId = $resolver->resolve($request);

        $this->assertSame(32, strlen($traceId));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $traceId);
        $this->assertNotSame(str_repeat('0', 32), $traceId);
    }

    public function test_middleware_starts_trace_context_from_query(): void
    {
        $middleware = new ApiTraceMiddleware(
            new TraceParamResolver(),
            new TraceIdResolver(),
        );
        $request = (new Request('GET', new Uri('http://localhost/api/users?trace=true')))
            ->withHeader('traceparent', '00-' . self::TRACE_ID . '-' . self::SPAN_ID . '-01');
        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new \Hyperf\HttpMessage\Server\Response();
            }
        };

        $middleware->process($request, $handler);

        $this->assertTrue(TraceContext::isEnabled());
        $this->assertSame(self::TRACE_ID, TraceContext::traceId());
        $this->assertSame(self::SPAN_ID, TraceContext::spanId());
    }

    public function test_middleware_skips_when_trace_disabled_globally(): void
    {
        $this->container->get(\Hyperf\Contract\ConfigInterface::class)->set('api-response.trace.enabled', false);

        $middleware = new ApiTraceMiddleware(
            new TraceParamResolver(),
            new TraceIdResolver(),
        );
        $request = new Request('GET', new Uri('http://localhost/api/users?trace=true'));
        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new \Hyperf\HttpMessage\Server\Response();
            }
        };

        $middleware->process($request, $handler);

        $this->assertFalse(TraceContext::isEnabled());
    }
}
