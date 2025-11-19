<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpPermanentRedirect;
use Volunteersystem\Http\Exceptions\HttpRedirect;
use PHPUnit\Framework\TestCase;

class HttpPermanentRedirectTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpPermanentRedirect::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpPermanentRedirect('https://lorem.ipsum/foo/bar');
        $this->assertInstanceOf(HttpRedirect::class, $exception);
        $this->assertEquals(301, $exception->getStatusCode());
    }
}
