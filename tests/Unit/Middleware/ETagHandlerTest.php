<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware;

use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Middleware\ETagHandler;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;

class ETagHandlerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Middleware\ETagHandler::process
     */
    public function testRegister(): void
    {
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockForAbstractClass(RequestHandlerInterface::class);
        $request = Request::create('https://localhost')
            ->withHeader('If-None-Match', 'FooBarBaz');
        $originalResponse = (new Response())
            ->withHeader('ETag', '"FooBarBaz"')
            ->withHeader('original-header', 'value')
            ->withContent('Foo bar!');
        $this->setExpects($handler, 'handle', [$request], $originalResponse);

        $middleware = new ETagHandler();
        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('original-header'));
        $this->assertEquals('value', $response->getHeader('original-header')[0]);

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
    }

    /**
     * @covers \Volunteersystem\Middleware\ETagHandler::process
     */
    public function testRegisterNoChange(): void
    {
        /** @var RequestHandlerInterface|MockObject $handler */
        $handler = $this->getMockForAbstractClass(RequestHandlerInterface::class);
        $request = Request::create('https://localhost');
        $originalResponse = new Response();
        $this->setExpects($handler, 'handle', [$request], $originalResponse);

        $middleware = new ETagHandler();
        $response = $middleware->process($request, $handler);

        $this->assertEquals($originalResponse, $response);
    }
}
