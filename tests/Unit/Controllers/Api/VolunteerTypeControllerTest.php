<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\VolunteerTypeController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VolunteerTypeControllerTest extends ApiBaseControllerTest
{
    /**
     * @covers \Volunteersystem\Controllers\Api\VolunteerTypeController::index
     * @covers \Volunteersystem\Controllers\Api\Resources\VolunteerTypeResource::toArray
     */
    public function testIndex(): void
    {
        $items = VolunteerType::factory(3)->create();

        $controller = new VolunteerTypeController(new Response());

        $response = $controller->index();
        $this->validateApiResponse('/volunteertypes', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
        $this->assertCount(1, collect($data['data'])->filter(function ($item) use ($items) {
            $first = $items->first();
            return $item['name'] == $first->getAttribute('name')
                && $item['description'] == $first->getAttribute('description')
                && $item['restricted'] == $first->getAttribute('restricted');
        }));
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\VolunteerTypeController::ofUser
     * @covers \Volunteersystem\Controllers\Api\Resources\UserVolunteerTypeResource::toArray
     */
    public function testOfUser(): void
    {
        $user = User::factory()->create();
        $items = UserVolunteerType::factory(3)->create(['user_id' => $user->id]);

        $controller = new VolunteerTypeController(new Response());

        $response = $controller->ofUser(new Request([], [], ['user_id' => $user->id]));
        $this->validateApiResponse('/users/{id}/volunteertypes', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
        $this->assertCount(1, collect($data['data'])->filter(function ($item) use ($items) {
            return $item['volunteertype']['id'] == $items->first()->volunteerType->id;
        }));
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\VolunteerTypeController::ofUser
     */
    public function testEntriesOfUserSelf(): void
    {
        $user = User::factory()->create();

        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, $user);

        $request = new Request();
        $request = $request->withAttribute('user_id', 'self');

        $controller = new VolunteerTypeController(new Response());
        $controller->setAuth($auth);

        $response = $controller->ofUser($request);
        $this->validateApiResponse('/users/{id}/volunteertypes', 'get', $response);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\VolunteerTypeController::ofUser
     */
    public function testEntriesByUserNotFound(): void
    {
        $request = new Request();
        $request = $request->withAttribute('user_id', 42);

        $controller = new VolunteerTypeController(new Response());

        $this->expectException(ModelNotFoundException::class);
        $controller->ofUser($request);
    }
}
