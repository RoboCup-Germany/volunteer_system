<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer;

use Volunteersystem\Renderer\TwigEngine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment as Twig;
use Twig\Loader\LoaderInterface as LoaderInterface;

class TwigEngineTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Renderer\TwigEngine::__construct
     * @covers \Volunteersystem\Renderer\TwigEngine::get
     */
    public function testGet(): void
    {
        /** @var Twig|MockObject $twig */
        $twig = $this->createMock(Twig::class);

        $path = 'foo.twig';
        $twig->expects($this->once())
            ->method('render')
            ->with($path, ['lorem' => 'ipsum', 'shared' => 'data'])
            ->willReturn('LoremIpsum data!');

        $engine = new TwigEngine($twig);
        $engine->share('shared', 'data');

        $return = $engine->get($path, ['lorem' => 'ipsum']);
        $this->assertEquals('LoremIpsum data!', $return);
    }


    /**
     * @covers \Volunteersystem\Renderer\TwigEngine::canRender
     */
    public function testCanRender(): void
    {
        /** @var Twig|MockObject $twig */
        $twig = $this->createMock(Twig::class);
        /** @var LoaderInterface|MockObject $loader */
        $loader = $this->getMockForAbstractClass(LoaderInterface::class);

        $path = 'foo.twig';

        $twig->expects($this->once())
            ->method('getLoader')
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('exists')
            ->with($path)
            ->willReturn(true);

        $engine = new TwigEngine($twig);
        $return = $engine->canRender($path);
        $this->assertTrue($return);
    }
}
