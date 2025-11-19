<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Admin;

use Carbon\Carbon;
use Volunteersystem\Config\GoodieType;
use Volunteersystem\Controllers\Admin\UserGoodieController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Exceptions\HttpNotFound;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Controllers\ControllerTest;
use Volunteersystem\Test\Unit\HasDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class UserGoodieControllerTest extends ControllerTest
{
    use HasDatabase;

    /**
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::editGoodie
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::__construct
     */
    public function testIndex(): void
    {
        $this->config->set('goodie_type', GoodieType::Tshirt->value);
        $this->config->set('night_shifts', ['enabled' => false]);
        $request = $this->request->withAttribute('user_id', 1);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        $user = new User();
        User::factory()->create();

        $this->setExpects($this->response, 'withView', ['admin/user/edit-goodie.twig'], $this->response);

        $controller = new UserGoodieController($auth, $this->config, $this->log, $redirector, $this->response, $user);

        $controller->editGoodie($request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::editGoodie
     */
    public function testIndexUserNotFound(): void
    {
        $this->config->set('goodie_type', GoodieType::Goodie->value);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        $user = new User();

        $controller = new UserGoodieController($auth, $this->config, $this->log, $redirector, $this->response, $user);

        $this->expectException(ModelNotFoundException::class);
        $controller->editGoodie($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::editGoodie
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::checkActive
     */
    public function testEditShirtGoodieNone(): void
    {
        $this->config->set('goodie_type', GoodieType::None->value);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        $user = new User();

        $controller = new UserGoodieController($auth, $this->config, $this->log, $redirector, $this->response, $user);

        $this->expectException(HttpNotFound::class);
        $controller->editGoodie($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::saveGoodie
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::checkActive
     */
    public function testSaveShirtGoodieNone(): void
    {
        $this->config->set('goodie_type', GoodieType::None->value);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        $user = new User();

        $controller = new UserGoodieController($auth, $this->config, $this->log, $redirector, $this->response, $user);

        $this->expectException(HttpNotFound::class);
        $controller->saveGoodie($this->request);
    }

    /**
     * @todo Factor out separate tests. Isolated User, Config and permissions per test.
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::saveGoodie
     */
    public function testSaveGoodie(): void
    {
        $this->config->set('goodie_type', GoodieType::Tshirt->value);
        $request = $this->request
            ->withAttribute('user_id', 1)
            ->withParsedBody([
                'shirt_size' => 'S',
            ]);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $this->config->set('tshirt_sizes', ['S' => 'Small', 'XS' => 'Extra Small']);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        User::factory()
            ->has(State::factory())
            ->has(PersonalData::factory())
            ->create();

        $auth
            ->expects($this->exactly(6))
            ->method('can')
            ->with('admin_arrive')
            ->willReturnOnConsecutiveCalls(true, true, false, false, true, true);
        $this->setExpects($redirector, 'back', null, $this->response, $this->exactly(6));

        $controller = new UserGoodieController(
            $auth,
            $this->config,
            $this->log,
            $redirector,
            $this->response,
            new User()
        );
        $controller->setValidator(new Validator());

        // Set shirt size
        $controller->saveGoodie($request);

        $this->assertHasNotification('user.edit.success');
        $this->assertTrue($this->log->hasInfoThatContains('Updated user goodie state'));

        $user = User::find(1);
        $this->assertEquals('S', $user->personalData->shirt_size);
        $this->assertFalse($user->state->arrived);
        $this->assertFalse($user->state->active);
        $this->assertFalse($user->state->got_goodie);

        // Set active, arrived and got_goodie
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'S',
                'arrived'    => '1',
                'active'     => '1',
                'got_goodie'  => '1',
            ]);

        $controller->saveGoodie($request);

        $user = User::find(1);
        $this->assertTrue($user->state->active);
        $this->assertTrue($user->state->arrived);
        $this->assertTrue($user->state->got_goodie);

        // Shirt size not available
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'L',
            ]);

        try {
            $controller->saveGoodie($request);
            self::fail('Expected exception was not raised');
        } catch (ValidationException) {
            // ignore
        }
        $user = User::find(1);
        $this->assertEquals('S', $user->personalData->shirt_size);

        // Not allowed changing arrived
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'S',
                'arrived'    => '1',
            ]);

        $user->state->arrival_date = null;
        $user->state->save();
        $this->assertFalse($user->state->arrived);
        $controller->saveGoodie($request);
        $user = User::find(1);
        $this->assertFalse($user->state->arrived);

        // Goodie enabled but not a shirt
        $this->config->set('goodie_type', GoodieType::Goodie->value);
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'XS',
            ]);

        $controller->saveGoodie($request);
        $user = User::find(1);
        $this->assertEquals('S', $user->personalData->shirt_size);

        // Shirt enabled
        $this->config->set('goodie_type', GoodieType::Tshirt->value);
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'XS',
            ]);

        $controller->saveGoodie($request);
        $user = User::find(1);
        $this->assertEquals('XS', $user->personalData->shirt_size);

        // remove arrived
        $user->state->arrival_date = Carbon::now();
        $user->state->save();
        $request = $request
            ->withParsedBody([
                'shirt_size' => 'XS',
                'arrived'    => '',
            ]);

        $controller->saveGoodie($request);
        $user = User::find(1);
        $this->assertFalse($user->state->arrived);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\UserGoodieController::saveGoodie
     */
    public function testSaveGoodieUserNotFound(): void
    {
        $this->config->set('goodie_type', GoodieType::Goodie->value);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Redirector|MockObject $redirector */
        $redirector = $this->createMock(Redirector::class);
        $user = new User();

        $controller = new UserGoodieController($auth, $this->config, $this->log, $redirector, $this->response, $user);

        $this->expectException(ModelNotFoundException::class);
        $controller->editGoodie($this->request);
    }
}
