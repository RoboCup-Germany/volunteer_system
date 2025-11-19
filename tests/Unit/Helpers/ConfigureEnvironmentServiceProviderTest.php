<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Carbon\CarbonTimeZone;
use Volunteersystem\Config\Config;
use Volunteersystem\Environment;
use Volunteersystem\Exceptions\Handler;
use Volunteersystem\Helpers\ConfigureEnvironmentServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigureEnvironmentServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\ConfigureEnvironmentServiceProvider::register
     * @covers \Volunteersystem\Helpers\ConfigureEnvironmentServiceProvider::setupDevErrorHandler
     */
    public function testRegister(): void
    {
        $config = new Config(['timezone' => 'Australia/Eucla', 'environment' => 'production']);
        $this->app->instance('config', $config);

        $handler = new Handler();
        $this->app->instance('error.handler', $handler);

        /** @var ConfigureEnvironmentServiceProvider|MockObject $serviceProvider */
        $serviceProvider = $this->getMockBuilder(ConfigureEnvironmentServiceProvider::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['setTimeZone', 'displayErrors', 'errorReporting'])
            ->getMock();

        $serviceProvider->expects($this->exactly(2))
            ->method('setTimeZone')
            ->willReturnCallback(function (CarbonTimeZone $timeZone): void {
                $this->assertEquals('Australia/Eucla', $timeZone->getName());
            });
        $serviceProvider->expects($this->exactly(3))
            ->method('displayErrors')
            ->withConsecutive([false], [false], [true]);
        $serviceProvider->expects($this->exactly(1))
            ->method('errorReporting')
            ->with(E_ALL);

        $serviceProvider->register();
        $this->assertNotEquals(Environment::DEVELOPMENT, $handler->getEnvironment());

        $config->set('environment', 'development');
        $serviceProvider->register();
        $this->assertEquals(Environment::DEVELOPMENT, $handler->getEnvironment());
    }
}
