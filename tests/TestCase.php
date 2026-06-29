<?php

declare(strict_types=1);

namespace Tests;

use FeloZ\HyperfApiResponse\Support\ApiResponse;
use FeloZ\HyperfApiResponse\Support\ApiTrace;
use FeloZ\HyperfApiResponse\Support\Contracts\ApiResponseContract;
use FeloZ\HyperfApiResponse\Support\Trace\TraceContext;
use FeloZ\HyperfApiResponse\Support\Trace\TraceIdResolver;
use FeloZ\HyperfApiResponse\Support\Trace\TraceParamResolver;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\HttpMessage\Server\Response as PsrResponse;
use Hyperf\HttpServer\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Config([
            'api-response' => require dirname(__DIR__) . '/src/publish/api-response.php',
        ]);

        $this->container = new Container((new DefinitionSourceFactory(true))());
        $this->container->set(ConfigInterface::class, $config);
        $this->container->set(Response::class, new Response(new PsrResponse()));
        $this->container->set(ApiResponseContract::class, new ApiResponse(
            $this->container->get(Response::class)
        ));
        $this->container->set(ApiTrace::class, new ApiTrace());
        $this->container->set(TraceParamResolver::class, new TraceParamResolver());
        $this->container->set(TraceIdResolver::class, new TraceIdResolver());

        ApplicationContext::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        TraceContext::clear();
        ApiResponse::clearMacros();

        parent::tearDown();
    }

    protected function decodeResponse(PsrResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function setAppDebug(bool $debug): void
    {
        $this->container->get(ConfigInterface::class)->set(
            'api-response.app_debug',
            $debug
        );
    }
}
