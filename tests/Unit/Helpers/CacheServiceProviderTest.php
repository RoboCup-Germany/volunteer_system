<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Application;
use Volunteersystem\Helpers\Cache;
use Volunteersystem\Helpers\CacheServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class CacheServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\CacheServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();
        $app->instance('path.cache', '/tmp');

        $serviceProvider = new CacheServiceProvider($app);
        $serviceProvider->register();

        $this->assertTrue($app->bound('cache'));
        $this->assertArrayHasKey(Cache::class, $app->contextual);

        $cache = $app->get(Cache::class);
        $this->assertInstanceOf(Cache::class, $cache);
    }
}
