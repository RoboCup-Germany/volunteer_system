<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\UsesAuthServiceProvider;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Controllers\Api\Stub\UsesAuthImplementation;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UsesAuthServiceProviderTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\Api\UsesAuthServiceProvider::register
     */
    public function testRegister(): void
    {
        $serviceProvider = new UsesAuthServiceProvider($this->app);
        $serviceProvider->register();

        $user = new User();

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, $user);
        $this->app->instance(Authenticator::class, $auth);

        /** @var UsesAuthImplementation $instance */
        $instance = $this->app->make(UsesAuthImplementation::class);

        $this->assertEquals($user, $instance->user('self'));
    }
}
