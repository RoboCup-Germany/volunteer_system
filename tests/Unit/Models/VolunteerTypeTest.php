<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;

class VolunteerTypeTest extends ModelTest
{
    /**
     * @return array<array{boolean, string, string, string}>
     */
    public function hasContactInfoDataProvider(): array
    {
        return [
            [false, '', '', ''],
            [true, 'Foo', '', ''],
            [true, '', 'BAR', ''],
            [true, '', '', 'baz@localhost'],
            [true, 'Foo', 'BAR', 'baz@localhost'],
        ];
    }

    /**
     * @covers       \Volunteersystem\Models\VolunteerType::hasContactInfo
     * @dataProvider hasContactInfoDataProvider
     */
    public function testHasContactInfo(bool $expected, ?string $name, ?string $dect, ?string $email): void
    {
        $model = new VolunteerType([
            'contact_name'  => $name,
            'contact_dect'  => $dect,
            'contact_email' => $email,
        ]);

        $this->assertEquals($expected, $model->hasContactInfo());
    }

    /**
     * @covers \Volunteersystem\Models\VolunteerType::userVolunteerTypes
     */
    public function testUserVolunteerTypes(): void
    {
        User::factory(2)->create();
        $user1 = User::factory()->create();
        User::factory(1)->create();
        $user2 = User::factory()->create();

        $volunteerType = VolunteerType::create(['name' => 'Test']);

        $volunteerType->userVolunteerTypes()->attach($user1);
        $volunteerType->userVolunteerTypes()->attach($user2);

        /** @var UserVolunteerType $userVolunteerType */
        $userVolunteerType = UserVolunteerType::find(1);
        $this->assertEquals($volunteerType->id, $userVolunteerType->volunteerType->id);

        $volunteertypes = $volunteerType->userVolunteerTypes;
        $this->assertCount(2, $volunteertypes);
    }

    /**
     * @covers \Volunteersystem\Models\VolunteerType::shiftEntries
     */
    public function testShiftEntries(): void
    {
        $volunteerType = VolunteerType::create(['name' => 'test type']);

        ShiftEntry::factory(3)->create(['volunteer_type_id' => $volunteerType->id]);

        $volunteerType = VolunteerType::find(1);
        $this->assertCount(3, $volunteerType->shiftEntries);
    }

    /**
     * @covers \Volunteersystem\Models\VolunteerType::neededBy
     */
    public function testNeededBy(): void
    {
        $volunteerType = VolunteerType::create(['name' => 'test type']);

        $this->assertCount(0, $volunteerType->neededBy);

        NeededVolunteerType::factory(4)->create(['volunteer_type_id' => $volunteerType->id]);

        $volunteerType = VolunteerType::find(1);
        $this->assertCount(4, $volunteerType->neededBy);
    }

    /**
     * @covers \Volunteersystem\Models\VolunteerType::boot
     */
    public function testBoot(): void
    {
        VolunteerType::factory()->create(['name' => 'foo']);
        VolunteerType::factory()->create(['name' => 'bar']);
        VolunteerType::factory()->create(['name' => 'baz']);
        VolunteerType::factory()->create(['name' => 'lorem']);
        VolunteerType::factory()->create(['name' => 'ipsum']);

        $this->assertEquals(
            ['bar', 'baz', 'foo', 'ipsum', 'lorem'],
            VolunteerType::all()->map(fn(VolunteerType $volunteerType) => $volunteerType->toArray())->pluck('name')->toArray()
        );
    }
}
