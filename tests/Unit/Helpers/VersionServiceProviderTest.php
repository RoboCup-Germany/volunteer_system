<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Application;
use Volunteersystem\Helpers\Version;
use Volunteersystem\Helpers\VersionServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class VersionServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\VersionServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();
        $app->instance('path', '/tmp');
        $app->instance('path.storage.app', '/tmp');

        $serviceProvider = new VersionServiceProvider($app);
        $serviceProvider->register();

        $this->assertArrayHasKey(Version::class, $app->contextual);
    }
}
