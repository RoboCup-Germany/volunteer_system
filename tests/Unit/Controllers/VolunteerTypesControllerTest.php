<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Controllers\VolunteerTypesController;
use Volunteersystem\Http\Response;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class VolunteerTypesControllerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\VolunteerTypesController::__construct
     * @covers \Volunteersystem\Controllers\VolunteerTypesController::about
     */
    public function testIndex(): void
    {
        /** @var Response|MockObject $response */
        $response = $this->createMock(Response::class);

        $this->setExpects(
            $response,
            'withView',
            ['pages/volunteertypes/about']
        );

        $controller = new VolunteerTypesController($response);
        $controller->about();
    }
}
