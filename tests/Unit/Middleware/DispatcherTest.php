<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Application;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Middleware\Dispatcher;
use Volunteersystem\Test\Unit\Middleware\Stub\ReturnResponseMiddlewareHandler;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass as Reflection;
use TypeError;

class DispatcherTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::__construct
     */
    public function testInit(): void
    {
        /** @var Application|MockObject $container */
        $container = $this->createMock(Application::class);

        $dispatcher = new Dispatcher([], $container);
        $this->assertInstanceOf(MiddlewareInterface::class, $dispatcher);
        $this->assertInstanceOf(RequestHandlerInterface::class, $dispatcher);

        $reflection = new Reflection(get_class($dispatcher));
        $property = $reflection->getProperty('container');
        $this->assertEquals($container, $property->getValue($dispatcher));
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::process
     */
    public function testProcess(): void
    {
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->onlyMethods(['handle'])
            ->getMock();

        $dispatcher->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $return = $dispatcher->process($request, $handler);
        $this->assertEquals($response, $return);

        $reflection = new Reflection(get_class($dispatcher));
        $property = $reflection->getProperty('next');

        $this->assertEquals($handler, $property->getValue($dispatcher));
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::handle
     */
    public function testHandle(): void
    {
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var MiddlewareInterface|MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);

        $dispatcher = new Dispatcher([$middleware]);
        $middleware->expects($this->once())
            ->method('process')
            ->with($request, $dispatcher)
            ->willReturn($response);

        $return = $dispatcher->handle($request);
        $this->assertEquals($response, $return);
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::handle
     */
    public function testHandleNext(): void
    {
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->createMock(RequestHandlerInterface::class);

        $dispatcher = new Dispatcher();
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $reflection = new Reflection(get_class($dispatcher));
        $property = $reflection->getProperty('next');
        $property->setValue($dispatcher, $handler);

        $return = $dispatcher->handle($request);
        $this->assertEquals($response, $return);
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::handle
     */
    public function testHandleNoMiddleware(): void
    {
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);

        $this->expectException(LogicException::class);

        $dispatcher = new Dispatcher();
        $dispatcher->handle($request);
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::handle
     */
    public function testHandleCallResolve(): void
    {
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->createMock(ServerRequestInterface::class);
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        /** @var MiddlewareInterface|MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);

        /** @var Dispatcher|MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder(Dispatcher::class)
            ->setConstructorArgs([[MiddlewareInterface::class, MiddlewareInterface::class]])
            ->onlyMethods(['resolveMiddleware'])
            ->getMock();

        $dispatcher->expects($this->exactly(2))
            ->method('resolveMiddleware')
            ->with(MiddlewareInterface::class)
            ->willReturnOnConsecutiveCalls($middleware, null);

        $middleware->expects($this->once())
            ->method('process')
            ->with($request, $dispatcher)
            ->willReturn($response);

        $return = $dispatcher->handle($request);
        $this->assertEquals($response, $return);

        $this->expectException(TypeError::class);
        $dispatcher->handle($request);
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::handle
     */
    public function testHandleCallResolveInvalidTypeResolved(): void
    {
        $instance = new Dispatcher([new ReturnResponseMiddlewareHandler(new Response())]);

        $this->expectException(InvalidArgumentException::class);
        $instance->handle(new Request());
    }

    /**
     * @covers \Volunteersystem\Middleware\Dispatcher::setContainer
     */
    public function testSetContainer(): void
    {
        /** @var Application|MockObject $container */
        $container = $this->createMock(Application::class);

        $middleware = new Dispatcher();
        $middleware->setContainer($container);

        $reflection = new Reflection(get_class($middleware));
        $property = $reflection->getProperty('container');

        $this->assertEquals($container, $property->getValue($middleware));
    }
}
