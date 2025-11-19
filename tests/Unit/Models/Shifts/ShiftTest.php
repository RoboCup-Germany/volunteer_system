<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Config\Config;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Models\ModelTest;
use Illuminate\Database\Eloquent\Collection;

class ShiftTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::shiftType
     * @covers \Volunteersystem\Models\Shifts\Shift::location
     * @covers \Volunteersystem\Models\Shifts\Shift::createdBy
     * @covers \Volunteersystem\Models\Shifts\Shift::updatedBy
     */
    public function testShiftType(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var ShiftType $shiftType */
        $shiftType = ShiftType::factory()->create();
        /** @var Location $location */
        $location = Location::factory()->create();

        $model = new Shift([
            'title'          => 'Test shift',
            'description'    => 'Some description',
            'url'            => 'https://foo.bar/map',
            'start'          => Carbon::now(),
            'end'            => Carbon::now(),
            'shift_type_id'  => $shiftType->id,
            'location_id'    => $location->id,
            'transaction_id' => '',
            'created_by'     => $user1->id,
            'updated_by'     => $user2->id,
        ]);
        $model->save();

        $model = Shift::find(1);

        $this->assertEquals($shiftType->id, $model->shiftType->id);
        $this->assertEquals($location->id, $model->location->id);
        $this->assertEquals($user1->id, $model->createdBy->id);
        $this->assertEquals($user2->id, $model->updatedBy->id);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::neededVolunteerTypes
     */
    public function testNeededVolunteerTypes(): void
    {
        /** @var Collection|Shift[] $shifts */
        $shifts = Shift::factory(3)->create();

        $this->assertCount(0, Shift::find(1)->neededVolunteerTypes);

        (NeededVolunteerType::factory()->make(['shift_id' => $shifts[0]->id, 'location_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['shift_id' => $shifts[0]->id, 'location_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['shift_id' => $shifts[1]->id, 'location_id' => null]))->save();
        (NeededVolunteerType::factory()->make(['shift_id' => $shifts[2]->id, 'location_id' => null]))->save();

        $this->assertCount(2, Shift::find(1)->neededVolunteerTypes);
        $this->assertEquals(1, Shift::find(1)->neededVolunteerTypes[0]->id);
        $this->assertEquals(2, Shift::find(1)->neededVolunteerTypes[1]->id);
        $this->assertEquals(3, Shift::find(2)->neededVolunteerTypes->first()->id);
        $this->assertEquals(4, Shift::find(3)->neededVolunteerTypes->first()->id);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::schedule
     */
    public function testSchedule(): void
    {
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->create();
        /** @var Collection|Shift[] $shifts */
        $shifts = Shift::factory(3)->create();

        (new ScheduleShift(['shift_id' => $shifts[0]->id, 'schedule_id' => $schedule->id, 'guid' => 'a']))->save();
        (new ScheduleShift(['shift_id' => $shifts[1]->id, 'schedule_id' => $schedule->id, 'guid' => 'b']))->save();
        (new ScheduleShift(['shift_id' => $shifts[2]->id, 'schedule_id' => $schedule->id, 'guid' => 'c']))->save();

        $this->assertEquals(1, Shift::find(1)->schedule->id);
        $this->assertEquals(1, Shift::find(2)->schedule->id);
        $this->assertEquals(1, Shift::find(3)->schedule->id);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::scheduleShift
     */
    public function testScheduleShift(): void
    {
        /** @var Schedule $schedule */
        $schedule = Schedule::factory()->create();
        /** @var Collection|Shift[] $shifts */
        $shifts = Shift::factory(4)->create();

        (new ScheduleShift(['shift_id' => $shifts[0]->id, 'schedule_id' => $schedule->id, 'guid' => 'd']))->save();
        (new ScheduleShift(['shift_id' => $shifts[1]->id, 'schedule_id' => $schedule->id, 'guid' => 'e']))->save();
        (new ScheduleShift(['shift_id' => $shifts[2]->id, 'schedule_id' => $schedule->id, 'guid' => 'f']))->save();

        $this->assertEquals('d', Shift::find(1)->scheduleShift->guid);
        $this->assertEquals('e', Shift::find(2)->scheduleShift->guid);
        $this->assertEquals('f', Shift::find(3)->scheduleShift->guid);
        $this->assertNull(Shift::find(4)->scheduleShift?->guid);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::shiftEntries
     */
    public function testShiftEntries(): void
    {
        /** @var Shift $shift */
        $shift = Shift::factory()->make();
        $shift->save();

        ShiftEntry::factory(5)->create(['shift_id' => $shift->id]);

        $this->assertCount(5, $shift->shiftEntries);
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::scopeNeedsUsers
     */
    public function testScopeNeedsUsers(): void
    {
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        /** @var Shift $shift */
        $shift = Shift::factory()->create();
        Shift::factory()->create();

        $this->assertCount(2, Shift::all());
        $this->assertCount(0, Shift::scopes('needsUsers')->get());

        NeededVolunteerType::factory()->create(['volunteer_type_id' => $volunteerType->id, 'shift_id' => $shift->id]);

        $this->assertTrue(Shift::count() >= 2);
        $this->assertCount(1, Shift::scopes('needsUsers')->get());
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::scopeNeedsUsers
     */
    public function testScopeNeedsUsersFromSchedule(): void
    {
        /** @var Schedule $schedule1 */
        $schedule1 = Schedule::factory()->create(['needed_from_shift_type' => true]);
        /** @var Schedule $schedule2 */
        $schedule2 = Schedule::factory()->create(['needed_from_shift_type' => false]);
        $shiftType = $schedule1->shiftType;
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        /** @var Shift $shift1 Via schedule shift type */
        $shift1 = Shift::factory()->create(['shift_type_id' => $shiftType->id]);
        /** @var Shift $shift2 Via schedule location */
        $shift2 = Shift::factory()->create();
        /** @var Shift $shift3 Direct */
        $shift3 = Shift::factory()->create();
        /** @var Shift $shift4 Via schedule location, no needed volunteer types */
        $shift4 = Shift::factory()->create();
        /** @var Shift $shift5 Empty shift */
        $shift5 = Shift::factory()->create();
        $location = $shift2->location;

        ScheduleShift::factory()->create(['shift_id' => $shift1->id, 'schedule_id' => $schedule1->id]);
        ScheduleShift::factory()->create(['shift_id' => $shift2->id, 'schedule_id' => $schedule2->id]);
        ScheduleShift::factory()->create(['shift_id' => $shift4->id, 'schedule_id' => $schedule2->id]);

        NeededVolunteerType::factory()->create(['volunteer_type_id' => $volunteerType->id, 'shift_type_id' => $shiftType->id]);
        NeededVolunteerType::factory()->create(['volunteer_type_id' => $volunteerType->id, 'location_id' => $location->id]);
        NeededVolunteerType::factory()->create(['volunteer_type_id' => $volunteerType->id, 'shift_id' => $shift3->id]);

        $this->assertTrue(Shift::count() >= 5);

        $shifts = Shift::scopes('needsUsers')->get()->pluck('id');
        $this->assertContains($shift1->id, $shifts, 'Shift should be selected via schedule shift type');
        $this->assertContains($shift2->id, $shifts, 'Shift should be selected via schedule location selected');
        $this->assertContains($shift3->id, $shifts, 'Shift should be selected via direct requirement selected');
        $this->assertNotContains($shift4->id, $shifts, 'Empty schedule location shift selected');
        $this->assertNotContains($shift5->id, $shifts, 'Empty shift selected');
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::nextShift
     */
    public function testNextShift(): void
    {
        $location = Location::factory()->create();
        $shiftType = ShiftType::factory()->create();
        $shift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket start',
            'start' => Carbon::now(),
        ]);
        $nextShift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket start',
            'start' => Carbon::now()->addHour(),
        ]);
        $otherShift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket starts',
            'start' => Carbon::now()->addHours(3),
        ]);

        $this->assertEquals($nextShift->id, $shift->nextShift()->id);
        $this->assertEquals($otherShift->id, $nextShift->nextShift()->id);
        $this->assertNull($otherShift->nextShift());
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::previousShift
     */
    public function testPreviousShift(): void
    {
        $location = Location::factory()->create();
        $shiftType = ShiftType::factory()->create();
        $shift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket start',
            'end' => Carbon::now(),
        ]);
        $previousShift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket start',
            'end' => Carbon::now()->subHour(),
        ]);
        $otherShift = Shift::factory()->create([
            'location_id' => $location->id,
            'shift_type_id' => $shiftType->id,
            'title' => 'Rocket starts',
            'end' => Carbon::now()->subHours(3),
        ]);

        $this->assertEquals($previousShift->id, $shift->previousShift()->id);
        $this->assertEquals($otherShift->id, $previousShift->previousShift()->id);
        $this->assertNull($otherShift->previousShift());
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::isNightShift
     */
    public function testIsNightShiftDisabled(): void
    {
        $config = new Config(['night_shifts' => [
            'enabled'    => false,
            'start'      => 2,
            'end'        => 8,
            'multiplier' => 2,
        ]]);
        $this->app->instance('config', $config);

        $shift = new Shift([
            'start' => new Carbon('2042-01-01 04:00'),
            'end' => new Carbon('2042-01-01 05:00'),
        ]);

        // At night but disabled
        $this->assertFalse($shift->isNightShift());
    }

    /**
     * @return array{0: string, 1: string, 2: boolean}[]
     */
    public function nightShiftData(): array
    {
        // $start, $end, $isNightShift
        return [
            // Is night shift
            ['2042-01-01 04:00', '2042-01-01 05:00', true],
            // Is night shift
            ['2042-01-01 02:00', '2042-01-01 02:15', true],
            // Is night shift
            ['2042-01-01 07:45', '2042-01-01 08:00', true],
            // Starts as night shift
            ['2042-01-01 07:59', '2042-01-01 09:00', true],
            // Ends as night shift
            ['2042-01-01 00:00', '2042-01-01 02:01', true],
            // Equals night shift
            ['2042-01-01 02:00', '2042-01-01 08:00', true],
            // Contains night shift
            ['2042-01-01 01:00', '2042-01-01 09:00', true],
            // Too early
            ['2042-01-01 00:00', '2042-01-01 02:00', false],
            // Too late
            ['2042-01-01 08:00', '2042-01-01 10:00', false],
            // Out of range
            ['2042-01-01 23:00', '2042-01-02 01:00', false],
        ];
    }

    /**
     * @covers       \Volunteersystem\Models\Shifts\Shift::isNightShift
     * @dataProvider nightShiftData
     */
    public function testIsNightShiftEnabled(string $start, string $end, bool $isNightShift): void
    {
        $config = new Config(['night_shifts' => [
            'enabled'    => true,
            'start'      => 2,
            'end'        => 8,
            'multiplier' => 2,
        ]]);
        $this->app->instance('config', $config);

        $shift = new Shift([
            'start' => new Carbon($start),
            'end' => new Carbon($end),
        ]);

        $this->assertEquals($isNightShift, $shift->isNightShift());
    }

    /**
     * @covers \Volunteersystem\Models\Shifts\Shift::getNightShiftMultiplier
     */
    public function testGetNightShiftMultiplier(): void
    {
        $config = new Config(['night_shifts' => [
            'enabled'    => true,
            'start'      => 2,
            'end'        => 8,
            'multiplier' => 2,
        ]]);
        $this->app->instance('config', $config);

        $shift = new Shift([
            'start' => new Carbon('2042-01-01 02:00'),
            'end' => new Carbon('2042-01-01 04:00'),
        ]);

        $this->assertEquals(2, $shift->getNightShiftMultiplier());

        $config->set('night_shifts', array_merge($config->get('night_shifts'), ['enabled' => false]));
        $this->assertEquals(1, $shift->getNightShiftMultiplier());
    }
}
