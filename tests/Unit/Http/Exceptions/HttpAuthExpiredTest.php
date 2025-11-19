<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpAuthExpired;
use PHPUnit\Framework\TestCase;

class HttpAuthExpiredTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpAuthExpired::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpAuthExpired();
        $this->assertEquals(419, $exception->getStatusCode());
        $this->assertEquals('Authentication Expired', $exception->getMessage());

        $exception = new HttpAuthExpired('Oops!');
        $this->assertEquals('Oops!', $exception->getMessage());
    }
}
