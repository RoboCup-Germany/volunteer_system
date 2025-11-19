<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Models\ModelTest;

class ShiftEntryTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Shifts\ShiftEntry::shift
     * @covers \Volunteersystem\Models\Shifts\ShiftEntry::volunteerType
     * @covers \Volunteersystem\Models\Shifts\ShiftEntry::freeloadedBy
     */
    public function testShift(): void
    {
        /** @var Shift $shift */
        $shift = Shift::factory()->create();
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $freeloadedBy */
        $freeloadedBy = User::factory()->create();

        $model = new ShiftEntry();
        $model->shift()->associate($shift);
        $model->volunteerType()->associate($volunteerType);
        $model->user()->associate($user);
        $model->freeloadedBy()->associate($freeloadedBy);
        $model->save();

        $model = ShiftEntry::find(1);
        $this->assertEquals($shift->id, $model->shift->id);
        $this->assertEquals($volunteerType->id, $model->volunteerType->id);
        $this->assertEquals($freeloadedBy->id, $model->freeloadedBy->id);
        $this->assertEquals($user->id, $model->user->id);

        $this->assertArrayNotHasKey('freeloaded_comment', $model->toArray());
    }
}
