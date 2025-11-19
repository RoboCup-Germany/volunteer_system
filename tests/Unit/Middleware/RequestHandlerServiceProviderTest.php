<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Middleware\RequestHandler;
use Volunteersystem\Middleware\RequestHandlerServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use PHPUnit\Framework\MockObject\MockObject;

class RequestHandlerServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Middleware\RequestHandlerServiceProvider::register()
     */
    public function testRegister(): void
    {
        /** @var RequestHandler|MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandler::class);

        $app = $this->getApp(['make', 'instance', 'bind']);

        $app->expects($this->once())
            ->method('make')
            ->with(RequestHandler::class)
            ->willReturn($requestHandler);
        $app->expects($this->once())
            ->method('instance')
            ->with('request.handler', $requestHandler);
        $app->expects($this->once())
            ->method('bind')
            ->with(RequestHandler::class, 'request.handler');

        $serviceProvider = new RequestHandlerServiceProvider($app);
        $serviceProvider->register();
    }
}
