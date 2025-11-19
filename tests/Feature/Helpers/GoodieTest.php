<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Feature\Helpers;

use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Helpers\Goodie;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\Worklog;
use Volunteersystem\Test\Feature\ApplicationFeatureTest;

class GoodieTest extends ApplicationFeatureTest
{
    /** @var BaseModel[] */
    protected array $createdModels = [];

    /**
     * @covers \Volunteersystem\Helpers\Goodie::userScore
     * @covers \Volunteersystem\Helpers\Goodie::shiftScoreQuery
     * @covers \Volunteersystem\Helpers\Goodie::worklogScoreQuery
     */
    public function testUserScoreNightShift(): void
    {
        $user = new User(['name' => 'gn8', 'email' => 'g@n.8', 'password' => '', 'api_key' => '']);
        $user->save();
        $this->createdModels[] = $user;
        $workLog = new Worklog([
            'user_id' => $user->id,
            'hours' => 3.87,
            'creator_id' => $user->id,
            'description' => '',
            'worked_at' => Carbon::now()->subHour(),
        ]);
        $workLog->save();
        $this->createdModels[] = $workLog;
        $shiftType = new ShiftType([
            'name' => 'Type',
            'description' => '',
        ]);
        $shiftType->save();
        $this->createdModels[] = $shiftType;
        $location = new Location([
            'name' => 'Local',
        ]);
        $location->save();
        $this->createdModels[] = $location;
        $shift = new Shift([
            'title' => 'Shift',
            'start' => Carbon::create('2020-03-02 1:00'),
            'end' => Carbon::create('2020-03-02 4:00'),
            'shift_type_id' => $shiftType->id,
            'location_id' => $location->id,
            'created_by' => $user->id,
        ]);
        $shift->save();
        $this->createdModels[] = $shift;
        $volunteerType = new VolunteerType([
            'name' => 'VolunteerType',
        ]);
        $volunteerType->save();
        $this->createdModels[] = $volunteerType;
        $shiftEntry = new ShiftEntry([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'volunteer_type_id' => $volunteerType->id,
        ]);
        $shiftEntry->save();
        $this->createdModels[] = $shiftEntry;

        $result = Goodie::userScore($user);

        $this->assertEquals(9.87, round($result, 2));
    }

    private function deleteModels(): void
    {
        foreach ($this->createdModels as $model) {
            $model->delete();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createdModels = [];
        config([
            'night_shifts' => [
                'enabled' => true,
                'start' => 2,
                'end' => 6,
                'multiplier' => 2,
            ],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->deleteModels();
    }
}
