<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Config\Config;
use Volunteersystem\Middleware\LegacyMiddleware;
use Volunteersystem\Middleware\RouteDispatcher;
use Volunteersystem\Middleware\RouteDispatcherServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use FastRoute\Dispatcher as FastRouteDispatcher;
use Illuminate\Contracts\Container\ContextualBindingBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\MiddlewareInterface;

class RouteDispatcherServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Middleware\RouteDispatcherServiceProvider::register()
     */
    public function testRegister(): void
    {
        /** @var ContextualBindingBuilder|MockObject $bindingBuilder */
        $bindingBuilder = $this->createMock(ContextualBindingBuilder::class);
        /** @var FastRouteDispatcher|MockObject $routeDispatcher */
        $routeDispatcher = $this->getMockForAbstractClass(FastRouteDispatcher::class);
        $config = new Config(['environment' => 'development']);

        $app = $this->getApp(['alias', 'when', 'get']);

        $app->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['config'], ['path.cache.routes'])
            ->willReturn($config, '/foo/routes.cache');

        $app->expects($this->once())
            ->method('alias')
            ->with(RouteDispatcher::class, 'route.dispatcher');

        $app->expects($this->exactly(2))
            ->method('when')
            ->with(RouteDispatcher::class)
            ->willReturn($bindingBuilder);

        $bindingBuilder->expects($this->exactly(2))
            ->method('needs')
            ->withConsecutive(
                [FastRouteDispatcher::class],
                [MiddlewareInterface::class]
            )
            ->willReturn($bindingBuilder);

        $bindingBuilder->expects($this->exactly(2))
            ->method('give')
            ->with($this->callback(function ($subject) {
                if (is_callable($subject)) {
                    $subject();
                }

                return is_callable($subject) || $subject == LegacyMiddleware::class;
            }));

        /** @var RouteDispatcherServiceProvider|MockObject $serviceProvider */
        $serviceProvider = $this->getMockBuilder(RouteDispatcherServiceProvider::class)
            ->setConstructorArgs([$app])
            ->onlyMethods(['generateRouting'])
            ->getMock();

        $serviceProvider->expects($this->once())
            ->method('generateRouting')
            ->willReturn($routeDispatcher);

        $serviceProvider->register();
    }
}
