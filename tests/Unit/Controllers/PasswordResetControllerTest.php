<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Volunteersystem\Config\Config;
use Volunteersystem\Controllers\NotificationType;
use Volunteersystem\Controllers\PasswordResetController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Exceptions\HttpNotFound;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\Session as SessionModel;
use Volunteersystem\Models\User\PasswordReset;
use Volunteersystem\Models\User\User;
use Volunteersystem\Renderer\Renderer;
use Volunteersystem\Test\Unit\HasDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class PasswordResetControllerTest extends ControllerTest
{
    use ArraySubsetAsserts;
    use HasDatabase;

    protected array $args = [];

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::reset
     * @covers \Volunteersystem\Controllers\PasswordResetController::__construct
     */
    public function testReset(): void
    {
        $controller = $this->getController('pages/password/reset');
        $response = $controller->reset();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::postReset
     */
    public function testPostReset(): void
    {
        $this->initDatabase();
        $request = new Request([], ['email' => 'foo@bar.batz']);
        $user = $this->createUser();

        $controller = $this->getController(
            'pages/password/reset-success',
            ['type' => 'email']
        );
        /** @var TestLogger $log */
        $log = $this->args['log'];
        /** @var VolunteersystemMailer|MockObject $mailer */
        $mailer = $this->args['mailer'];
        $this->setExpects($mailer, 'sendViewTranslated');

        $controller->postReset($request);

        $this->assertNotEmpty((new PasswordReset())->find($user->id)->first());
        $this->assertTrue($log->hasInfoThatContains($user->name));
        $this->assertHasNoNotifications();
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::postReset
     */
    public function testPostResetInvalidRequest(): void
    {
        $request = new Request();

        $controller = $this->getController();

        $this->expectException(ValidationException::class);
        $controller->postReset($request);
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::postReset
     */
    public function testPostResetNoUser(): void
    {
        $this->initDatabase();
        $request = new Request([], ['email' => 'foo@bar.batz']);

        $controller = $this->getController(
            'pages/password/reset-success',
            ['type' => 'email']
        );

        $controller->postReset($request);
        $this->assertHasNoNotifications();
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::resetPassword
     * @covers \Volunteersystem\Controllers\PasswordResetController::requireToken
     */
    public function testResetPassword(): void
    {
        $this->initDatabase();

        $this->app->instance('config', new Config(['password_min_length' => 3]));
        $user = $this->createUser();
        $token = $this->createToken($user);
        $request = new Request([], [], ['token' => $token->token]);

        $controller = $this->getController('pages/password/reset-form');

        $controller->resetPassword($request);
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::resetPassword
     * @covers \Volunteersystem\Controllers\PasswordResetController::requireToken
     */
    public function testResetPasswordNoToken(): void
    {
        $this->initDatabase();
        $controller = $this->getController();

        $this->expectException(HttpNotFound::class);
        $controller->resetPassword(new Request());
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::postResetPassword
     */
    public function testPostResetPassword(): void
    {
        $this->initDatabase();

        $this->app->instance('config', new Config(['password_min_length' => 3]));
        $user = $this->createUser();
        $token = $this->createToken($user);
        $password = 'SomeRandomPasswordForAmazingSecurity';
        $request = new Request(
            [],
            ['password' => $password, 'password_confirmation' => $password],
            ['token' => $token->token]
        );
        SessionModel::factory()->create(); // Some other session
        SessionModel::factory(3)->create(['user_id' => $user->id]);

        $controller = $this->getController(
            'pages/password/reset-success',
            ['type' => 'reset']
        );

        $auth = new Authenticator($request, $this->args['session'], $user);
        $this->app->instance('authenticator', $auth);

        $response = $controller->postResetPassword($request);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEmpty((new PasswordReset())->find($user->id));
        $this->assertNotNull(auth()->authenticate($user->name, $password));
        $this->assertHasNoNotifications();

        $this->assertEmpty(
            SessionModel::whereUserId($user->id)->get(),
            'All user sessions should be deleted after successful password reset'
        );
        $this->assertCount(1, SessionModel::all()); // Another session should be still there
    }

    /**
     * @covers \Volunteersystem\Controllers\PasswordResetController::postResetPassword
     * @covers \Volunteersystem\Controllers\PasswordResetController::showView
     */
    public function testPostResetPasswordNotMatching(): void
    {
        $this->initDatabase();

        $this->app->instance('config', new Config(['password_min_length' => 3]));
        $user = $this->createUser();
        $token = $this->createToken($user);
        $password = 'SomeRandomPasswordForAmazingSecurity';
        $request = new Request(
            [],
            ['password' => $password, 'password_confirmation' => $password . 'OrNot'],
            ['token' => $token->token]
        );

        $controller = $this->getController('pages/password/reset-form');

        $controller->postResetPassword($request);
        $this->assertHasNotification('validation.password.confirmed', NotificationType::ERROR);
    }

    protected function getControllerArgs(): array
    {
        $response = new Response();
        $session = new Session(new MockArraySessionStorage());
        /** @var VolunteersystemMailer|MockObject $mailer */
        $mailer = $this->createMock(VolunteersystemMailer::class);
        $log = new TestLogger();
        $renderer = $this->createMock(Renderer::class);
        $response->setRenderer($renderer);

        $this->app->instance('session', $session);

        $this->session = $session;
        $this->response = $response;
        $this->log = $log;

        return $this->args = [
            'response' => $response,
            'session'  => $session,
            'mailer'   => $mailer,
            'log'      => $log,
            'renderer' => $renderer,
        ];
    }

    protected function getController(?string $view = null, ?array $data = null): PasswordResetController
    {
        /** @var Response $response */
        /** @var Session $session */
        /** @var VolunteersystemMailer|MockObject $mailer */
        /** @var TestLogger $log */
        /** @var Renderer|MockObject $renderer */
        list($response, $session, $mailer, $log, $renderer) = array_values($this->getControllerArgs());
        $controller = new PasswordResetController($response, $session, $mailer, $log);
        $controller->setValidator(new Validator());

        if ($view) {
            /** @var array|mixed[] $args */
            $args = [$view];
            if ($data) {
                $args[] = $data;
            }

            $renderer->expects($this->atLeastOnce())
                ->method('render')
                ->willReturnCallback(function ($template, $data = []) use ($args) {
                    $this->assertEquals($args[0], $template);
                    if (isset($args[1])) {
                        $this->assertArraySubset($args[1], $data);
                    }

                    return 'Foo';
                });
        }

        return $controller;
    }

    protected function createUser(): User
    {
        return User::factory()->create(['email' => 'foo@bar.batz']);
    }

    protected function createToken(User $user): PasswordReset
    {
        $reset = new PasswordReset(['user_id' => $user->id, 'token' => 'SomeTestToken123']);
        $reset->save();

        return $reset;
    }
}
