<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

namespace Arhitov\LaravelBilling\Tests;

use GuzzleHttp\Psr7\Message;
use Mockery;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use Http\Mock\Client as MockClient;
use Omnireceipt\Common\Contracts\Http\ClientInterface;
use Omnireceipt\Common\Contracts\Http\RequestInterface;
use Omnireceipt\Common\Http\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class MockTestCase extends FeatureTestCase
{
    private ?RequestInterface $mockRequest = null;

    private ?MockClient $mockClient = null;

    private ?ClientInterface $httpClient = null;

    private ?HttpRequest $httpRequest = null;

    protected function setUp(): void
    {
        parent::setUp();

        HttpClientDiscovery::prependStrategy(MockClientStrategy::class);
    }

    /**
     * @return RequestInterface[]
     */
    public function getMockedRequests(): array
    {
        return $this->mockClient->getRequests();
    }

    public function getMockHttpResponse($path): ResponseInterface
    {
        if ($path instanceof ResponseInterface) {
            return $path;
        }

        /** @var ResponseInterface $response */
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Mock/' . $path));;
        return $response;
    }

    public function setMockHttpResponse($paths): void
    {
        foreach ((array) $paths as $path) {
            $this->getMockClient()->addResponse($this->getMockHttpResponse($path));
        }
    }

    public function getMockRequest()
    {
        return $this->mockRequest ??= Mockery::mock(RequestInterface::class);
    }

    public function getMockClient(): MockClient
    {
        return $this->mockClient ??= new MockClient();
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient ??= new Client(
            $this->getMockClient()
        );
    }

    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest ??= new HttpRequest;
    }
}
