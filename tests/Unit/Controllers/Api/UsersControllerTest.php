<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\UsersController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\Contact;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\Settings;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\Worklog;
use PHPUnit\Framework\MockObject\MockObject;

class UsersControllerTest extends ApiBaseControllerTest
{
    /**
     * @covers \Volunteersystem\Controllers\Api\UsersController::index
     */
    public function testIndex(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $controller = new UsersController(new Response());

        $response = $controller->index();
        $this->validateApiResponse('/users', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);

        $firstUser = $data['data'][0];
        $this->assertArrayHasKey('id', $firstUser);
        $this->assertEquals($user->id, $firstUser['id']);
        $this->assertArrayHasKey('name', $firstUser);
        $this->assertEquals($user->name, $firstUser['name']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\UsersController::user
     * @covers \Volunteersystem\Controllers\Api\Resources\UserDetailResource::toArray
     * @covers \Volunteersystem\Controllers\Api\Resources\UserResource::toArray
     */
    public function testUser(): void
    {
        $user = User::factory()
            ->has(Contact::factory())
            ->has(PersonalData::factory())
            ->has(Settings::factory())
            ->has(State::factory())
            ->create();

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, $user, $this->atLeastOnce());

        $request = new Request();
        $request = $request->withAttribute('user_id', 'self');

        $controller = new UsersController(new Response());
        $controller->setAuth($auth);

        $response = $controller->user($request);
        $this->validateApiResponse('/users/{id}', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertEquals($user->id, $data['data']['id']);
        $this->assertArrayHasKey('name', $data['data']);
        $this->assertEquals($user->name, $data['data']['name']);
        $this->assertArrayHasKey('email', $data['data']);
        $this->assertArrayHasKey('dates', $data['data']);
        $this->assertArrayHasKey('contact', $data['data']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\UsersController::user
     */
    public function testUserById(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()
            ->has(Contact::factory())
            ->has(PersonalData::factory())
            ->has(Settings::factory())
            ->has(State::factory())
            ->create();

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, $user, $this->atLeastOnce());

        $request = new Request();
        $request = $request->withAttribute('user_id', $otherUser->id);

        $controller = new UsersController(new Response());
        $controller->setAuth($auth);

        $response = $controller->user($request);
        $this->validateApiResponse('/users/{id}', 'get', $response);

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertEquals($otherUser->id, $data['data']['id']);
        $this->assertArrayNotHasKey('dates', $data['data']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\UsersController::entriesByVolunteertype
     * @covers \Volunteersystem\Controllers\Api\Resources\UserVolunteerTypeReferenceResource::toArray
     */
    public function testEntriesByVolunteertype(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $controller = new UsersController(new Response());
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        $user->userVolunteerTypes()->attach($volunteerType);
        $request = new Request([], [], ['volunteertype_id' => $volunteerType->id]);

        $response = $controller->entriesByVolunteertype($request);
        $this->validateApiResponse('/volunteertypes/{id}/users', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);

        $firstEntry = $data['data'][0];
        $this->assertArrayHasKey('user', $firstEntry);
        $this->assertArrayHasKey('confirmed', $firstEntry);
        $this->assertArrayHasKey('supporter', $firstEntry);

        $firstUser = $firstEntry['user'];
        $this->assertArrayHasKey('id', $firstUser);
        $this->assertEquals($user->id, $firstUser['id']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\UsersController::worklogs
     * @covers \Volunteersystem\Controllers\Api\Resources\WorklogResource::toArray
     */
    public function testWorklogs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $controller = new UsersController(new Response());
        /** @var Worklog $worklog */
        $worklog = Worklog::factory()->create(['user_id' => $user->id, 'hours' => 1.23]);
        $request = new Request([], [], ['user_id' => $user->id]);

        $response = $controller->worklogs($request);
        $this->validateApiResponse('/users/{id}/worklogs', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertNotEmpty($data['data']);

        $firstEntry = $data['data'][0];
        $this->assertArrayHasKey('id', $firstEntry);
        $this->assertArrayHasKey('description', $firstEntry);
        $this->assertArrayHasKey('hours', $firstEntry);

        $this->assertEquals($worklog->hours, $firstEntry['hours']);
    }
}
