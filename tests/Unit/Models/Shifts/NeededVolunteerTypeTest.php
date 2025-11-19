<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Test\Unit\Models\ModelTest;

class NeededVolunteerTypeTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Shifts\NeededVolunteerType::location
     * @covers \Volunteersystem\Models\Shifts\NeededVolunteerType::shift
     * @covers \Volunteersystem\Models\Shifts\NeededVolunteerType::shiftType
     * @covers \Volunteersystem\Models\Shifts\NeededVolunteerType::volunteerType
     */
    public function testShift(): void
    {
        /** @var Location $location */
        $location = Location::factory()->create();
        /** @var Shift $shift */
        $shift = Shift::factory()->create();
        /** @var ShiftType $shiftType */
        $shiftType = ShiftType::factory()->create();
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();

        $model = new NeededVolunteerType();
        $model->location()->associate($location);
        $model->shift()->associate($shift);
        $model->shiftType()->associate($shiftType);
        $model->volunteerType()->associate($volunteerType);
        $model->count = 3;
        $model->save();

        $model = NeededVolunteerType::find(1);
        $this->assertEquals($location->id, $model->location->id);
        $this->assertEquals($shift->id, $model->shift->id);
        $this->assertEquals($shiftType->id, $model->shiftType->id);
        $this->assertEquals($volunteerType->id, $model->volunteerType->id);
        $this->assertEquals(3, $model->count);
    }
}
