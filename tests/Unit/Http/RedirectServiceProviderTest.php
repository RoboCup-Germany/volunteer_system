<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http;

use Volunteersystem\Application;
use Volunteersystem\Http\RedirectServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class RedirectServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Http\RedirectServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();

        $serviceProvider = new RedirectServiceProvider($app);
        $serviceProvider->register();

        $this->assertTrue($app->has('redirect'));
    }
}
