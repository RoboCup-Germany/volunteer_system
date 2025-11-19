<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers;

use Volunteersystem\Controllers\NotificationType;
use Volunteersystem\Controllers\ShiftsController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\UrlGeneratorInterface;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\MockObject;

class ShiftsControllerTest extends ControllerTest
{
    protected Authenticator|MockObject $auth;
    protected Redirector|MockObject $redirect;
    protected UrlGeneratorInterface $url;
    protected User $user;

    /**
     * @covers \Volunteersystem\Controllers\ShiftsController::random
     * @covers \Volunteersystem\Controllers\ShiftsController::__construct
     */
    public function testRandomNonShiftsFound(): void
    {
        $this->createModels();

        $this->setExpects($this->redirect, 'to', ['http://localhost/shifts'], $this->response);
        $this->setExpects($this->auth, 'user', null, $this->user);

        $controller = new ShiftsController($this->auth, $this->redirect, $this->url);

        $return = $controller->random();
        $this->assertEquals($this->response, $return);
        $this->assertHasNotification('notification.shift.no_next_found', NotificationType::WARNING);
    }

    /**
     * @covers \Volunteersystem\Controllers\ShiftsController::random
     * @covers \Volunteersystem\Controllers\ShiftsController::getNextFreeShifts
     * @covers \Volunteersystem\Controllers\ShiftsController::queryShiftEntries
     */
    public function testRandom(): void
    {
        $this->createModels();
        $this->setExpects($this->auth, 'user', null, $this->user, $this->atLeastOnce());
        $start = Carbon::now()->addHour();

        $otherUser = User::factory()->create();
        [$userVolunteerType, $otherVolunteerType] = VolunteerType::factory(2)->create();
        [$possibleShift1, $possibleShift2, $otherVolunteerTypeShift, $alreadySubscribedShift] = Shift::factory(4)
            ->create(['start' => $start, 'end' => $start->addHours(2)]);
        $this->user->userVolunteerTypes()->attach($userVolunteerType, ['confirm_user_id' => $this->user->id]);
        NeededVolunteerType::factory()->create([
            'shift_id' => $possibleShift1->id,
            'volunteer_type_id' => $userVolunteerType->id,
            'count' => 2,
        ]);
        NeededVolunteerType::factory()->create([
            'shift_id' => $possibleShift2->id,
            'volunteer_type_id' => $userVolunteerType->id,
            'count' => 1,
        ]);
        NeededVolunteerType::factory()->create([
            'shift_id' => $otherVolunteerTypeShift->id,
            'volunteer_type_id' => $otherVolunteerType->id,
            'count' => 3,
        ]);
        ShiftEntry::factory()->create([
            'shift_id' => $alreadySubscribedShift->id,
            'volunteer_type_id' => $userVolunteerType->id,
            'user_id' => $this->user->id,
        ]);

        $otherUser->userVolunteerTypes()->attach($userVolunteerType, ['confirm_user_id' => $otherUser->id]);
        ShiftEntry::factory()->create([
            'shift_id' => $possibleShift1->id,
            'volunteer_type_id' => $userVolunteerType->id,
            'user_id' => $otherUser,
        ]);

        $this->redirect->expects($this->exactly(10))
            ->method('to')
            ->willReturnCallback(function (string $url) use ($possibleShift1, $possibleShift2) {
                parse_str(parse_url($url)['query'] ?? '', $parameters);
                $this->assertTrue(Str::startsWith($url, 'http://localhost/shifts'));
                $this->assertArrayHasKey('shift_id', $parameters);
                $shiftId = $parameters['shift_id'] ?? 0;
                $this->assertTrue(in_array($shiftId, [$possibleShift1->id, $possibleShift2->id]));
                return $this->response;
            });

        $controller = new ShiftsController($this->auth, $this->redirect, $this->url);

        $return = $controller->random();
        $this->assertEquals($this->response, $return);

        // Try multiple times
        for ($i = 1; $i < 10; $i++) {
            $controller->random();
        }
    }

    protected function createModels(): void
    {
        $this->user = User::factory()->create();

        $this->auth = $this->createMock(Authenticator::class);

        $this->redirect = $this->createMock(Redirector::class);

        $this->url = $this->app->make(UrlGeneratorInterface::class);
    }
}
