<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Events;

use Volunteersystem\Config\Config;
use Volunteersystem\Events\EventDispatcher;
use Volunteersystem\Events\EventsServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class EventsServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Events\EventsServiceProvider::register
     * @covers \Volunteersystem\Events\EventsServiceProvider::registerEvents
     */
    public function testRegister(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->app->instance(EventDispatcher::class, $dispatcher);
        $dispatcher->expects($this->exactly(3))
            ->method('listen')
            ->withConsecutive(
                ['test.event', 'someFunction'],
                ['another.event', 'Foo\Bar@baz'],
                ['another.event', [$this, 'testRegister']]
            );

        $config = new Config([
            'event-handlers' => [
                'test.event' => 'someFunction',
                'another.event' => ['Foo\Bar@baz', [$this, 'testRegister']],
            ],
        ]);
        $this->app->instance('config', $config);

        /** @var EventsServiceProvider $provider */
        $provider = $this->app->make(EventsServiceProvider::class);

        $provider->register();
    }
}
