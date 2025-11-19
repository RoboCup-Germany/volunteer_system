<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Test\Unit\Models\ModelTest;
use Illuminate\Database\Eloquent\Collection;

class ScheduleTest extends ModelTest
{
    protected array $data = [
        'url'            => 'https://foo.bar/schedule.xml',
        'name'           => 'Testing',
        'shift_type'     => 1,
        'needed_from_shift_type' => false,
        'minutes_before' => 10,
        'minutes_after'  => 10,
    ];

    public function setUp(): void
    {
        parent::setUp();

        ShiftType::factory()->create(['id' => 1]);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Schedule::activeLocations
     */
    public function testActiveLocations(): void
    {
        $schedule = new Schedule($this->data);
        $schedule->save();

        $location = Location::factory()->create();
        $schedule->activeLocations()->attach($location);

        $schedule = Schedule::find($schedule->id);
        $this->assertCount(1, $schedule->activeLocations);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Schedule::scheduleShifts
     */
    public function testScheduleShifts(): void
    {
        Shift::factory(3)->create();
        $schedule = new Schedule($this->data);
        $schedule->save();

        (new ScheduleShift(['shift_id' => 1, 'schedule_id' => $schedule->id, 'guid' => 'a']))->save();
        (new ScheduleShift(['shift_id' => 2, 'schedule_id' => $schedule->id, 'guid' => 'b']))->save();
        (new ScheduleShift(['shift_id' => 3, 'schedule_id' => $schedule->id, 'guid' => 'c']))->save();

        $this->assertCount(3, $schedule->scheduleShifts);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Schedule::shifts
     */
    public function testShifts(): void
    {
        $schedule = new Schedule($this->data);
        $schedule->save();

        /** @var Collection|Shift[] $shifts */
        $shifts = Shift::factory(3)->create();

        (new ScheduleShift(['shift_id' => $shifts[0]->id, 'schedule_id' => $schedule->id, 'guid' => 'a']))->save();
        (new ScheduleShift(['shift_id' => $shifts[1]->id, 'schedule_id' => $schedule->id, 'guid' => 'b']))->save();
        (new ScheduleShift(['shift_id' => $shifts[2]->id, 'schedule_id' => $schedule->id, 'guid' => 'c']))->save();

        $this->assertCount(3, $schedule->shifts);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Schedule::shiftType
     */
    public function testShiftType(): void
    {
        $st = new ShiftType(['name' => 'Shift Type', 'description' => '']);
        $st->save();

        $schedule = new Schedule($this->data);
        $schedule->shiftType()->associate($st);
        $schedule->save();

        $this->assertEquals('Shift Type', Schedule::find(1)->shiftType->name);
    }
}
