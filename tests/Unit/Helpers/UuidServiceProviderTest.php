<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Application;
use Volunteersystem\Helpers\Uuid;
use Volunteersystem\Helpers\UuidServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use Illuminate\Support\Str;
use ReflectionProperty;

class UuidServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\UuidServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();

        $serviceProvider = new UuidServiceProvider($app);
        $serviceProvider->register();

        $uuidFactoryReference = (new ReflectionProperty(Str::class, 'uuidFactory'))
            ->getValue();

        $this->assertIsCallable($uuidFactoryReference);
        $this->assertIsString($uuidFactoryReference);
        $this->assertEquals(Uuid::class . '::uuid', $uuidFactoryReference);

        $this->assertTrue(Str::isUuid(Str::uuid()), 'Is a UUID');
    }
}
