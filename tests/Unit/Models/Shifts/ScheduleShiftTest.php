<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Test\Unit\Models\ModelTest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleShiftTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Shifts\ScheduleShift::schedule
     * @covers \Volunteersystem\Models\Shifts\ScheduleShift::shift
     */
    public function testScheduleShifts(): void
    {
        ShiftType::factory()->create();
        $schedule = new Schedule([
            'url' => 'https://lorem.ipsum/schedule.xml',
            'name' => 'Test',
            'shift_type' => 1,
            'minutes_before' => 15,
            'minutes_after' => 15,
        ]);
        $schedule->save();
        /** @var Shift $shift */
        $shift = Shift::factory()->create();

        $scheduleShift = new ScheduleShift(['guid' => 'a']);
        $scheduleShift->schedule()->associate($schedule);
        $scheduleShift->shift()->associate($shift);
        $scheduleShift->save();

        /** @var ScheduleShift $scheduleShift */
        $scheduleShift = (new ScheduleShift())->find(1);
        $this->assertInstanceOf(BelongsTo::class, $scheduleShift->schedule());
        $this->assertEquals($schedule->id, $scheduleShift->schedule->id);
        $this->assertInstanceOf(BelongsTo::class, $scheduleShift->shift());
        $this->assertEquals($shift->id, $scheduleShift->shift->id);
    }
}
