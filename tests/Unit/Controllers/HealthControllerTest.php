<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Controllers\HealthController;
use Volunteersystem\Http\Response;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HealthControllerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\HealthController::__construct
     * @covers \Volunteersystem\Controllers\HealthController::index
     */
    public function testIndex(): void
    {
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);
        $this->setExpects($response, 'withContent', ['Ok'], $response);

        $controller = new HealthController($response);
        $controller->index();
    }
}
