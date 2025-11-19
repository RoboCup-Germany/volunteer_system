<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer;

use Volunteersystem\Test\Unit\Renderer\Stub\EngineImplementation;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Renderer\Engine::share
     */
    public function testShare(): void
    {
        $engine = new EngineImplementation();
        $engine->share(['foo' => ['bar' => 'baz', 'lorem' => 'ipsum']]);
        $engine->share(['foo' => ['lorem' => 'dolor']]);
        $engine->share('key', 'value');

        $this->assertEquals(
            ['foo' => ['bar' => 'baz', 'lorem' => 'dolor'], 'key' => 'value'],
            $engine->getSharedData()
        );
    }
}
