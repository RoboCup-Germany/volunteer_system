<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Carbon\Carbon;
use Volunteersystem\Helpers\Schedule\Day;
use Volunteersystem\Helpers\Schedule\Room;
use Volunteersystem\Test\Unit\TestCase;

class DayTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\Day::__construct
     * @covers \Volunteersystem\Helpers\Schedule\Day::getDate
     * @covers \Volunteersystem\Helpers\Schedule\Day::getStart
     * @covers \Volunteersystem\Helpers\Schedule\Day::getEnd
     * @covers \Volunteersystem\Helpers\Schedule\Day::getIndex
     * @covers \Volunteersystem\Helpers\Schedule\Day::getRooms
     */
    public function testCreate(): void
    {
        $day = new Day(
            '2000-01-01',
            new Carbon('2000-01-01T03:00:00+01:00'),
            new Carbon('2000-01-02T05:59:00+00:00'),
            1
        );
        $this->assertEquals('2000-01-01', $day->getDate());
        $this->assertEquals('2000-01-01T03:00:00+01:00', $day->getStart()->format(Carbon::RFC3339));
        $this->assertEquals('2000-01-02T05:59:00+00:00', $day->getEnd()->format(Carbon::RFC3339));
        $this->assertEquals(1, $day->getIndex());
        $this->assertEquals([], $day->getRooms());

        $rooms = [
            new Room('Foo'),
            new Room('Bar'),
        ];
        $day = new Day(
            '2001-01-01',
            new Carbon('2001-01-01T03:00:00+01:00'),
            new Carbon('2001-01-02T05:59:00+00:00'),
            1,
            $rooms
        );
        $this->assertEquals($rooms, $day->getRooms());
    }
}
