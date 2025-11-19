<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpNotFound;
use PHPUnit\Framework\TestCase;

class HttpNotFoundTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpNotFound::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpNotFound();
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('', $exception->getMessage());

        $exception = new HttpNotFound('Nothing to see here!');
        $this->assertEquals('Nothing to see here!', $exception->getMessage());
    }
}
