<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Container;

use Volunteersystem\Container\ServiceProvider;
use Volunteersystem\Test\Unit\Container\Stub\ServiceProviderImplementation;
use Volunteersystem\Test\Unit\ServiceProviderTest as ServiceProviderTestCase;

class ServiceProviderTest extends ServiceProviderTestCase
{
    /**
     * @covers \Volunteersystem\Container\ServiceProvider::__construct
     * @covers \Volunteersystem\Container\ServiceProvider::register
     * @covers \Volunteersystem\Container\ServiceProvider::boot
     */
    public function testRegister(): void
    {
        $app = $this->getApp();

        $serviceProvider = new ServiceProviderImplementation($app);

        $this->assertInstanceOf(ServiceProvider::class, $serviceProvider);

        $serviceProvider->register();
        $serviceProvider->boot();
    }
}
