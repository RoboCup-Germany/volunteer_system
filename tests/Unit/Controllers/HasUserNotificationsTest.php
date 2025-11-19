<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Controllers\NotificationType;
use Volunteersystem\Test\Unit\Controllers\Stub\HasUserNotificationsImplementation;
use Volunteersystem\Test\Unit\TestCase;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class HasUserNotificationsTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\HasUserNotifications::getNotifications
     * @covers \Volunteersystem\Controllers\HasUserNotifications::addNotification
     */
    public function testNotifications(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->app->instance('session', $session);

        $notify = new HasUserNotificationsImplementation();
        $notify->add('Foo', NotificationType::ERROR);
        $notify->add('Bar', NotificationType::WARNING);
        $notify->add(['Baz', 'Lorem'], NotificationType::INFORMATION);
        $notify->add(['Hm', ['test'], 'some' => ['Uff', 'sum']], NotificationType::MESSAGE);
        $notify->add(['some' => ['it']], NotificationType::MESSAGE);

        $this->assertEquals([
            NotificationType::ERROR->value       => new Collection(['Foo']),
            NotificationType::WARNING->value     => new Collection(['Bar']),
            NotificationType::INFORMATION->value => new Collection(['Baz', 'Lorem']),
            NotificationType::MESSAGE->value     => new Collection(['Hm', 'test', 'Uff', 'sum', 'it']),
        ], $notify->get());
    }
}
