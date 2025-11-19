<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http;

use Volunteersystem\Config\Config;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\UrlGenerator;
use Volunteersystem\Test\Unit\TestCase;

class UrlGeneratorTest extends TestCase
{
    public function provideLinksTo(): array
    {
        return [
            ['/foo/path', '/foo/path', 'https://foo.bar/foo/path', [], 'https://foo.bar/foo/path'],
            ['foo', '/foo', 'https://foo.bar/foo', [], 'https://foo.bar/foo'],
            ['foo', '/foo', 'https://f.b/foo', ['test' => 'abc', 'bla' => 'foo'], 'https://f.b/foo?test=abc&bla=foo'],
        ];
    }

    /**
     * @dataProvider provideLinksTo
     * @covers       \Volunteersystem\Http\UrlGenerator::to
     * @covers       \Volunteersystem\Http\UrlGenerator::generateUrl
     *
     * @param string[] $arguments
     */
    public function testTo(
        string $urlToPath,
        string $path,
        string $willReturn,
        array $arguments,
        string $expectedUrl
    ): void {
        $request = $this->getMockBuilder(Request::class)
            ->getMock();
        $request->expects($this->once())
            ->method('getUriForPath')
            ->with($path)
            ->willReturn($willReturn);
        $this->app->instance('request', $request);
        $this->app->instance('config', new Config());

        $urlGenerator = new UrlGenerator();

        $url = $urlGenerator->to($urlToPath, $arguments);
        $this->assertEquals($expectedUrl, $url);
    }

    /**
     * @covers \Volunteersystem\Http\UrlGenerator::to
     */
    public function testToWithValidUrl(): void
    {
        $url = new UrlGenerator();
        $this->app->instance('config', new Config());

        $this->assertEquals('https://foo.bar/batz', $url->to('https://foo.bar/batz'));
        $this->assertEquals('https://some.url?lorem=ipsum', $url->to('https://some.url', ['lorem' => 'ipsum']));
        $this->assertEquals('mailto:foo@bar.batz', $url->to('mailto:foo@bar.batz'));
        $this->assertEquals('#some-anchor', $url->to('#some-anchor'));
    }

    /**
     * @covers \Volunteersystem\Http\UrlGenerator::to
     * @covers \Volunteersystem\Http\UrlGenerator::generateUrl
     */
    public function testToWithApplicationURL(): void
    {
        $this->app->instance('config', new Config(['url' => 'https://foo.bar/base/']));

        $url = new UrlGenerator();

        $this->assertEquals('https://foo.bar/base/test', $url->to('test'));
        $this->assertEquals('https://foo.bar/base/test', $url->to('/test'));
        $this->assertEquals('https://foo.bar/base/lorem?ipsum=dolor', $url->to('/lorem', ['ipsum' => 'dolor']));

        $this->app->instance('config', new Config(['url' => 'https://foo.bar/base']));
        $this->assertEquals('https://foo.bar/base/test', $url->to('test'));
        $this->assertEquals('https://foo.bar/base/test', $url->to('/test'));
    }

    /**
     * @covers \Volunteersystem\Http\UrlGenerator::isValidUrl
     */
    public function testIsValidUrl(): void
    {
        $url = new UrlGenerator();

        $this->assertTrue($url->isValidUrl('https://foo.bar'));
        $this->assertTrue($url->isValidUrl('#foo-bar'));
        $this->assertTrue($url->isValidUrl('tel:+123456'));
        $this->assertTrue($url->isValidUrl('ftp://foo@bar.batz'));

        $this->assertFalse($url->isValidUrl('foo/bar'));
        $this->assertFalse($url->isValidUrl('foo/uff://bar'));
        $this->assertFalse($url->isValidUrl('lorem/ipsum#dolor'));
    }
}
