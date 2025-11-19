<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\ShiftTypeController;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\Shifts\ShiftType;

class ShiftTypeControllerTest extends ApiBaseControllerTest
{
    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftTypeController::index
     * @covers \Volunteersystem\Controllers\Api\Resources\ShiftTypeResource::toArray
     */
    public function testIndex(): void
    {
        $items = ShiftType::factory(3)->create();

        $controller = new ShiftTypeController(new Response());

        $response = $controller->index();
        $this->validateApiResponse('/shifttypes', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
        $this->assertCount(1, collect($data['data'])->filter(function ($item) use ($items) {
            return $item['name'] == $items->first()->getAttribute('name');
        }));
    }
}
