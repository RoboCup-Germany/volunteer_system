<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Volunteersystem\Http\Exceptions\HttpRedirect;
use PHPUnit\Framework\TestCase;

class HttpRedirectTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @covers \Volunteersystem\Http\Exceptions\HttpRedirect::__construct
     */
    public function testConstruct(): void
    {
        $exception = new HttpRedirect('https://lorem.ipsum/foo/bar');
        $this->assertEquals(302, $exception->getStatusCode());
        $this->assertArraySubset(['Location' => 'https://lorem.ipsum/foo/bar'], $exception->getHeaders());

        $exception = new HttpRedirect('/test', 301, ['lorem' => 'ipsum']);
        $this->assertEquals(301, $exception->getStatusCode());
        $this->assertArraySubset(['lorem' => 'ipsum'], $exception->getHeaders());
    }
}
