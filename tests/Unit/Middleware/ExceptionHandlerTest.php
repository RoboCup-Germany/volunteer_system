<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Application;
use Volunteersystem\Exceptions\Handler;
use Volunteersystem\Http\Response;
use Volunteersystem\Middleware\ExceptionHandler;
use Volunteersystem\Test\Unit\Middleware\Stub\ExceptionMiddlewareHandler;
use Volunteersystem\Test\Unit\Middleware\Stub\ReturnResponseMiddlewareHandler;
use Illuminate\Contracts\Container\Container as ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExceptionHandlerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Middleware\ExceptionHandler::__construct
     * @covers \Volunteersystem\Middleware\ExceptionHandler::process
     */
    public function testRegister(): void
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockForAbstractClass(ContainerInterface::class);
        /** @var ServerRequestInterface|MockObject $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        /** @var ResponseInterface|MockObject $response */
        $response = $this->getMockBuilder(Response::class)->getMock();
        /** @var Handler|MockObject $errorHandler */
        $errorHandler = $this->getMockBuilder(Handler::class)->getMock();
        $returnResponseHandler = new ReturnResponseMiddlewareHandler($response);
        $throwExceptionHandler = new ExceptionMiddlewareHandler();

        Application::setInstance($container);

        $container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['error.handler'], ['psr7.response'])
            ->willReturnOnConsecutiveCalls($errorHandler, $response);

        $response->expects($this->once())
            ->method('withContent')
            ->willReturn($response);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(500)
            ->willReturn($response);

        $handler = new ExceptionHandler($container);
        $return = $handler->process($request, $returnResponseHandler);
        $this->assertEquals($response, $return);

        $return = $handler->process($request, $throwExceptionHandler);
        $this->assertEquals($response, $return);
    }
}
