<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Middleware\LegacyMiddleware;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;

class LegacyMiddlewareTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Middleware\LegacyMiddleware::__construct
     * @covers \Volunteersystem\Middleware\LegacyMiddleware::process
     */
    public function testProcess404(): void
    {
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockForAbstractClass(RequestHandlerInterface::class);

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);

        $request = new Request(['p' => 'notAvailablePage']);
        $this->app->instance('request', $request);

        $this->mockTranslator();

        $response = new Response();
        $middleware = $this->getMockBuilder(LegacyMiddleware::class)
            ->setConstructorArgs([$this->app, $auth])
            ->onlyMethods(['renderPage'])
            ->getMock();
        $middleware->expects($this->once())
            ->method('renderPage')
            ->with(404, 'page.404.title', 'page.404.text')
            ->willReturn($response);

        $middleware->process($request, $handler);
    }

    /**
     * @covers \Volunteersystem\Middleware\LegacyMiddleware::process
     */
    public function testProcess(): void
    {
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockForAbstractClass(RequestHandlerInterface::class);

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $auth->expects($this->exactly(2))
            ->method('can')
            ->withConsecutive(['users.arrive.list'], ['admin_arrive'])
            ->willReturnOnConsecutiveCalls(true, false);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'admin-arrive']);
        $this->app->instance('request', $request);

        $response = new Response();
        /** @var LegacyMiddleware|MockObject $middleware */
        $middleware = $this->getMockBuilder(LegacyMiddleware::class)
            ->setConstructorArgs([$this->app, $auth])
            ->onlyMethods(['loadPage', 'renderPage'])
            ->getMock();
        $middleware->expects($this->once())
            ->method('loadPage')
            ->with('admin_arrive')
            ->willReturn(['title', 'content']);
        $middleware->expects($this->once())
            ->method('renderPage')
            ->with('admin_arrive', 'title', 'content')
            ->willReturn($response);

        $middleware->process($request, $handler);
    }
}
