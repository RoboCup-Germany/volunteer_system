<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Carbon\Carbon;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\Worklog;

class WorklogTest extends ModelTest
{
    /**
     * @covers \Volunteersystem\Models\Worklog::creator
     */
    public function testCreator(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $worklog = new Worklog();
        $worklog->user()->associate($user1);
        $worklog->creator()->associate($user2);
        $worklog->hours = 4.2;
        $worklog->description = 'Lorem ipsum';
        $worklog->worked_at = new Carbon();
        $worklog->night_shift = false;
        $worklog->save();

        $savedWorklog = Worklog::first();
        $this->assertEquals($user2->name, $savedWorklog->creator->name);
    }
}
