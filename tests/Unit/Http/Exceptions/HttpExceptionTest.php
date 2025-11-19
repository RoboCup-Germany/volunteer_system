<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpException::__construct
     * @covers \Volunteersystem\Http\Exceptions\HttpException::getHeaders
     * @covers \Volunteersystem\Http\Exceptions\HttpException::getStatusCode
     */
    public function testConstruct(): void
    {
        $exception = new HttpException(123);
        $this->assertEquals(123, $exception->getStatusCode());
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals([], $exception->getHeaders());

        $exception = new HttpException(404, 'Nothing found', ['page' => '/test']);
        $this->assertEquals('Nothing found', $exception->getMessage());
        $this->assertEquals(['page' => '/test'], $exception->getHeaders());
    }
}
