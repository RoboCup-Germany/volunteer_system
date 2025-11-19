<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Config\Config;
use Volunteersystem\Controllers\HomeController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HomeControllerTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\HomeController::__construct
     * @covers \Volunteersystem\Controllers\HomeController::index
     */
    public function testIndex(): void
    {
        $config = new Config(['home_site' => '/foo']);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, new User());
        /** @var Redirector|MockObject $redirect */
        $redirect = $this->createMock(Redirector::class);
        $this->setExpects($redirect, 'to', ['/foo'], new Response());

        $controller = new HomeController($auth, $config, $redirect);
        $controller->index();
    }
}
