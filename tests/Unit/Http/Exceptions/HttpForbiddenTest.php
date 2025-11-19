<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpForbidden;
use PHPUnit\Framework\TestCase;

class HttpForbiddenTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpForbidden::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpForbidden();
        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals('', $exception->getMessage());

        $exception = new HttpForbidden('Go away!');
        $this->assertEquals('Go away!', $exception->getMessage());
    }
}
