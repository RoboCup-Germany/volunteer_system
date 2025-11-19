<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\LocationsController;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\Location;

class LocationsControllerTest extends ApiBaseControllerTest
{
    /**
     * @covers \Volunteersystem\Controllers\Api\LocationsController::index
     * @covers \Volunteersystem\Controllers\Api\Resources\LocationResource::toArray
     */
    public function testIndex(): void
    {
        $items = Location::factory(3)->create();

        $controller = new LocationsController(new Response());

        $response = $controller->index();
        $this->validateApiResponse('/locations', 'get', $response);

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
