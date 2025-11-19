<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http;

use Volunteersystem\Application;
use Volunteersystem\Http\HttpClientServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use GuzzleHttp\Client as GuzzleClient;

class HttpClientServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Http\HttpClientServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();

        $serviceProvider = new HttpClientServiceProvider($app);
        $serviceProvider->register();

        /** @var GuzzleClient $guzzle */
        $guzzle = $app->make(GuzzleClient::class);
        $config = $guzzle->getConfig();

        $this->assertFalse($config['http_errors']);
        $this->assertArrayHasKey('timeout', $config);
    }
}
