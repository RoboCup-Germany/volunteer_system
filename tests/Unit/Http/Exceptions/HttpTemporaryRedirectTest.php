<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\HttpRedirect;
use Volunteersystem\Http\Exceptions\HttpTemporaryRedirect;
use PHPUnit\Framework\TestCase;

class HttpTemporaryRedirectTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpTemporaryRedirect::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpTemporaryRedirect('https://lorem.ipsum/foo/bar');
        $this->assertInstanceOf(HttpRedirect::class, $exception);
        $this->assertEquals(302, $exception->getStatusCode());
    }
}
