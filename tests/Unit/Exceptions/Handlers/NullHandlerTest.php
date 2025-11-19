<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Exceptions\Handlers;

use Volunteersystem\Exceptions\Handlers\NullHandler;
use Volunteersystem\Http\Request;
use ErrorException;
use PHPUnit\Framework\TestCase;

class NullHandlerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Exceptions\Handlers\NullHandler::render
     */
    public function testRender(): void
    {
        $handler = new NullHandler();
        $request = new Request();
        $exception = new ErrorException();

        $this->expectOutputString('');
        $handler->render($request, $exception);
    }
}
