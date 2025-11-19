<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Config\Config;
use Volunteersystem\Controllers\DesignController;
use Volunteersystem\Http\Response;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DesignControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRenderer();
        $this->mockTranslator();
    }

    /**
     * @covers \Volunteersystem\Controllers\DesignController::__construct
     * @covers \Volunteersystem\Controllers\DesignController::index
     */
    public function testIndex(): void
    {
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('withView')
            ->with('pages/design')
            ->willReturnCallback(function (string $view, array $data) use ($response) {
                $this->assertTrue(isset($data['demo_user']));
                $this->assertTrue(isset($data['demo_user_2']));
                $this->assertIsArray($data['themes']);

                return $response;
            });
        $config = new Config(['themes' => [42 => ['name' => 'Foo']]]);

        $controller = new DesignController($response, $config);
        $return = $controller->index();

        $this->assertEquals($response, $return);
    }
}
