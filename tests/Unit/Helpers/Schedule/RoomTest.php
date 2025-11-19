<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\Event;
use Volunteersystem\Helpers\Schedule\Room;
use Volunteersystem\Helpers\Uuid;
use Volunteersystem\Test\Unit\TestCase;

class RoomTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\Room::__construct
     * @covers \Volunteersystem\Helpers\Schedule\Room::getName
     * @covers \Volunteersystem\Helpers\Schedule\Room::getEvents
     * @covers \Volunteersystem\Helpers\Schedule\Room::getGuid
     */
    public function testCreateDefault(): void
    {
        $room = new Room('Test');
        $this->assertEquals('Test', $room->getName());
        $this->assertEquals([], $room->getEvents());
        $this->assertNull($room->getGuid());
    }
    /**
     * @covers \Volunteersystem\Helpers\Schedule\Room::__construct
     * @covers \Volunteersystem\Helpers\Schedule\Room::getName
     * @covers \Volunteersystem\Helpers\Schedule\Room::getEvents
     * @covers \Volunteersystem\Helpers\Schedule\Room::setEvents
     * @covers \Volunteersystem\Helpers\Schedule\Room::getGuid
     */
    public function testCreate(): void
    {
        $uuid = Uuid::uuid();
        $events = [$this->createMock(Event::class), $this->createMock(Event::class)];
        $events2 = [$this->createMock(Event::class)];
        $room = new Room('Test2', $uuid, $events);
        $this->assertEquals($events, $room->getEvents());
        $this->assertEquals($uuid, $room->getGuid());

        $room->setEvents($events2);
        $this->assertEquals($events2, $room->getEvents());
    }
}
