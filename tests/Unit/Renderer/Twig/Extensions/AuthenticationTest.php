<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer\Twig\Extensions;

use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Models\User\User;
use Volunteersystem\Renderer\Twig\Extensions\Authentication;
use Volunteersystem\Test\Unit\HasDatabase;
use PHPUnit\Framework\MockObject\MockObject;

class AuthenticationTest extends ExtensionTest
{
    use HasDatabase;

    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Authentication::__construct
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Authentication::getFunctions
     */
    public function testGetFunctions(): void
    {
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);

        $extension = new Authentication($auth);
        $functions = $extension->getFunctions();

        $this->assertExtensionExists('is_user', [$extension, 'isAuthenticated'], $functions);
        $this->assertExtensionExists('is_guest', [$extension, 'isGuest'], $functions);
        $this->assertExtensionExists('can', [$auth, 'can'], $functions);
        $this->assertExtensionExists('canAny', [$auth, 'canAny'], $functions);
    }

    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Authentication::isAuthenticated
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Authentication::isGuest
     */
    public function testIsAuthenticated(): void
    {
        $this->initDatabase();
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $user = new User();

        $auth->expects($this->exactly(4))
            ->method('user')
            ->willReturnOnConsecutiveCalls(
                null,
                null,
                $user,
                $user
            );

        $extension = new Authentication($auth);

        $this->assertFalse($extension->isAuthenticated());
        $this->assertTrue($extension->isGuest());

        $this->assertTrue($extension->isAuthenticated());
        $this->assertFalse($extension->isGuest());
    }
}
