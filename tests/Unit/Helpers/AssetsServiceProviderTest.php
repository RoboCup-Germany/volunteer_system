<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Application;
use Volunteersystem\Helpers\Assets;
use Volunteersystem\Helpers\AssetsServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class AssetsServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\AssetsServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();
        $app->instance('path.assets.public', '/tmp');

        $serviceProvider = new AssetsServiceProvider($app);
        $serviceProvider->register();

        $this->assertArrayHasKey(Assets::class, $app->contextual);
    }
}
