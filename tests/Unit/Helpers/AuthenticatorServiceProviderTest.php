<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Application;
use Volunteersystem\Config\Config;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Helpers\AuthenticatorServiceProvider;
use Volunteersystem\Http\Request;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatorServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Helpers\AuthenticatorServiceProvider::register()
     */
    public function testRegister(): void
    {
        $app = new Application();
        $app->bind(ServerRequestInterface::class, Request::class);

        $config = new Config([
            'password_algorithm' => PASSWORD_DEFAULT,
            'auth_guest_role'    => 42,
            'auth_default_role'  => 1337,
        ]);
        $app->instance('config', $config);

        $serviceProvider = new AuthenticatorServiceProvider($app);
        $serviceProvider->register();

        $this->assertInstanceOf(Authenticator::class, $app->get(Authenticator::class));
        $this->assertInstanceOf(Authenticator::class, $app->get('authenticator'));
        $this->assertInstanceOf(Authenticator::class, $app->get('auth'));

        /** @var Authenticator $auth */
        $auth = $app->get(Authenticator::class);
        $this->assertEquals(PASSWORD_DEFAULT, $auth->getPasswordAlgorithm());
        $this->assertEquals(42, $auth->getGuestRole());
        $this->assertEquals(1337, $auth->getDefaultRole());
    }
}
