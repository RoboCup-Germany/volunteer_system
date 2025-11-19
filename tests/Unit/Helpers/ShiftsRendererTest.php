<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Helpers\ShiftsRenderer;
use Volunteersystem\Helpers\Uuid;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\MockObject;

class ShiftsRendererTest extends TestCase
{
    use HasDatabase;

    /**
     * @covers \Volunteersystem\Helpers\ShiftsRenderer::render
     */
    public function testRender(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        /** @var Schedule $scheduleLocation */
        $scheduleLocation = Schedule::factory()->create(['needed_from_shift_type' => false]);
        /** @var Schedule $scheduleType */
        $scheduleType = Schedule::factory()->create(['needed_from_shift_type' => true]);

        /** @var Shift $shiftNormal */
        $shiftNormal = Shift::factory()->create();
        $shiftNormal->scheduleShift()->delete();
        /** @var Shift $shiftScheduleLocation */
        $shiftScheduleLocation = Shift::factory()->create();
        /** @var Shift $shiftScheduleType */
        $shiftScheduleType = Shift::factory()->create();

        $scheduleShiftLocation = new ScheduleShift(['guid' => Str::uuid()]);
        $scheduleShiftLocation->schedule()->associate($scheduleLocation);
        $scheduleShiftLocation->shift()->associate($shiftScheduleLocation);
        $scheduleShiftLocation->save();
        $scheduleShiftType = new ScheduleShift(['guid' => Str::uuid()]);
        $scheduleShiftType->schedule()->associate($scheduleType);
        $scheduleShiftType->shift()->associate($shiftScheduleType);
        $scheduleShiftType->save();

        $shiftNormal->neededVolunteerTypes()->create(['volunteer_type_id' => $volunteerType->id, 'count' => 3]);

        ShiftEntry::factory()->create([
            'shift_id' => $shiftNormal,
            'volunteer_type_id' => $volunteerType,
            'user_id' => $user,
        ]);

        /** @var ShiftsRenderer|MockObject $renderer */
        $renderer = $this->getMockBuilder(ShiftsRenderer::class)
            ->onlyMethods(['renderShiftCalendar'])
            ->getMock();
        $renderer->expects($this->once())
            ->method('renderShiftCalendar')
            ->willReturnCallback(function (array | Collection $shifts, array $neededVolunteerTypes, array $shiftEntries) {
                $this->assertCount(3, $shifts);

                $volunteerType = $neededVolunteerTypes[1][0];
                $this->assertNotEmpty($volunteerType);
                $this->assertArrayHasKey('name', $volunteerType);
                $this->assertArrayHasKey('restricted', $volunteerType);
                $this->assertArrayHasKey('shift_self_signup', $volunteerType);

                $entry = $shiftEntries[1][0];
                $this->assertNotEmpty($entry);

                return 'rendered table';
            });

        $output = $renderer->render(Shift::all());
        $this->assertEquals('rendered table', $output);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDatabase();
        Str::createUuidsUsing(Uuid::class . '::uuid');
    }
}
