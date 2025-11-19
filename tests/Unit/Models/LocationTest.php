<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\Shift;
use Illuminate\Database\Eloquent\Collection;

class LocationTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Location::activeForSchedules
     */
    public function testActiveForSchedules(): void
    {
        $location = new Location(['name' => 'Test location']);
        $location->save();

        $schedule = Schedule::factory()->create();
        $location->activeForSchedules()->attach($schedule);

        $location = Location::find($location->id);
        $this->assertCount(1, $location->activeForSchedules);
    }

    /**
     * @covers \Volunteersystem\Models\Location::shifts
     */
    public function testShifts(): void
    {
        $location = new Location(['name' => 'Test location']);
        $location->save();

        /** @var Shift $shift */
        Shift::factory()->create(['location_id' => 1]);

        $location = Location::find(1);
        $this->assertCount(1, $location->shifts);
    }

    /**
     * @covers \Volunteersystem\Models\Location::neededVolunteerTypes
     */
    public function testNeededVolunteerTypes(): void
    {
        /** @var Collection|Location[] $shifts */
        $shifts = Location::factory(3)->create();

        $this->assertCount(0, Location::find(1)->neededVolunteerTypes);

        (NeededVolunteerType::factory()->make(['location_id' => $shifts[0]->id, 'shift_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['location_id' => $shifts[0]->id, 'shift_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['location_id' => $shifts[1]->id, 'shift_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['location_id' => $shifts[2]->id, 'shift_id' => null]))->save();

        $this->assertCount(2, Location::find(1)->neededVolunteerTypes);
        $this->assertEquals(1, Location::find(1)->neededVolunteerTypes[0]->id);
        $this->assertEquals(2, Location::find(1)->neededVolunteerTypes[1]->id);
        $this->assertEquals(3, Location::find(2)->neededVolunteerTypes->first()->id);
        $this->assertEquals(4, Location::find(3)->neededVolunteerTypes->first()->id);
    }
}
