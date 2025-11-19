<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Test\Unit\Models\ModelTest;

class ShiftTypeTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Shifts\ShiftType::neededVolunteerTypes
     */
    public function testNeededVolunteerTypes(): void
    {
        $shiftType = new ShiftType(['name' => 'Another type', 'description' => '']);
        $shiftType->save();

        NeededVolunteerType::factory()->create(['shift_type_id' => 1]);

        $this->assertCount(1, ShiftType::find(1)->neededVolunteerTypes);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\ShiftType::schedules
     */
    public function testSchedules(): void
    {
        ShiftType::factory()->create();
        $shiftType = new ShiftType(['name' => 'Test type', 'description' => 'Foo bar baz']);
        $shiftType->save();

        Schedule::factory()->create(['shift_type' => 2]);
        Schedule::factory(2)->create(['shift_type' => 1]);

        $this->assertCount(2, ShiftType::find(1)->schedules);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\ShiftType::shifts
     */
    public function testShifts(): void
    {
        $shiftType = new ShiftType(['name' => 'Another type', 'description' => '']);
        $shiftType->save();

        Shift::factory()->create(['shift_type_id' => 1]);

        $this->assertCount(1, ShiftType::find(1)->shifts);
    }
}
