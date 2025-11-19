<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Application;
use Volunteersystem\Middleware\CallableHandler;
use Volunteersystem\Test\Unit\Middleware\Stub\HasStaticMethod;
use Volunteersystem\Test\Unit\Middleware\Stub\ResolvesMiddlewareTraitImplementation;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;

class ResolvesMiddlewareTraitTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Middleware\ResolvesMiddlewareTrait::isMiddleware
     * @covers \Volunteersystem\Middleware\ResolvesMiddlewareTrait::resolveMiddleware
     */
    public function testResolveMiddleware(): void
    {
        /** @var Application|MockObject $container */
        $container = $this->createMock(Application::class);
        $middlewareInterface = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $callable = [HasStaticMethod::class, 'foo'];

        $container->expects($this->exactly(3))
            ->method('make')
            ->withConsecutive(
                ['FooBarClass'],
                [CallableHandler::class, ['callable' => $callable]],
                ['UnresolvableClass']
            )
            ->willReturnOnConsecutiveCalls(
                $middlewareInterface,
                $middlewareInterface,
                null
            );

        $middleware = new ResolvesMiddlewareTraitImplementation($container);

        $return = $middleware->callResolveMiddleware('FooBarClass');
        $this->assertEquals($middlewareInterface, $return);

        $return = $middleware->callResolveMiddleware($callable);
        $this->assertEquals($middlewareInterface, $return);

        $this->expectException(InvalidArgumentException::class);
        $middleware->callResolveMiddleware('UnresolvableClass');
    }

    /**
     * @covers \Volunteersystem\Middleware\ResolvesMiddlewareTrait::resolveMiddleware
     */
    public function testResolveMiddlewareNotCallable(): void
    {
        /** @var Application|MockObject $container */
        $container = $this->createMock(Application::class);

        $middleware = new ResolvesMiddlewareTraitImplementation($container);

        $this->expectException(InvalidArgumentException::class);
        $middleware->callResolveMiddleware([new stdClass(), 'test']);
    }

    /**
     * @covers \Volunteersystem\Middleware\ResolvesMiddlewareTrait::resolveMiddleware
     */
    public function testResolveMiddlewareNoContainer(): void
    {
        $middlewareInterface = $this->getMockForAbstractClass(MiddlewareInterface::class);

        $middleware = new ResolvesMiddlewareTraitImplementation();
        $return = $middleware->callResolveMiddleware($middlewareInterface);

        $this->assertEquals($middlewareInterface, $return);

        $this->expectException(InvalidArgumentException::class);
        $middleware->callResolveMiddleware('FooBarClass');
    }
}
