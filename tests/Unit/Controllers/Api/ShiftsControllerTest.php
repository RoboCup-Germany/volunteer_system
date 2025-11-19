<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\ShiftsController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\User\Contact;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class ShiftsControllerTest extends ApiBaseControllerTest
{
    protected Location $location;
    protected Schedule $schedule1;
    protected Schedule $schedule2;
    protected Shift $shiftA;
    protected Shift $shiftB;
    protected Shift $shiftC;
    protected Shift $shiftD;
    protected ShiftType $shiftType;

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByLocation
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::shiftEntriesResponse
     * @covers \Volunteersystem\Controllers\Api\Resources\ShiftResource::toArray
     * @covers \Volunteersystem\Controllers\Api\Resources\ShiftTypeResource::toArray
     * @covers \Volunteersystem\Controllers\Api\Resources\ShiftWithEntriesResource::toArray
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::getNeededVolunteerTypes
     */
    public function testEntriesByLocation(): void
    {
        $request = new Request();
        $request = $request->withAttribute('location_id', $this->location->id);

        $controller = new ShiftsController(new Response());

        $response = $controller->entriesByLocation($request);
        $this->validateApiResponse('/locations/{id}/shifts', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);

        // First shift
        $shiftCData = $data['data'][0];
        $this->assertEquals($this->shiftC->title, $shiftCData['name'], 'Title is equal');
        $this->assertEquals($this->location->id, $shiftCData['location']['id'], 'Same location');
        $this->assertEquals($this->shiftC->shiftType->id, $shiftCData['shift_type']['id'], 'Shift type equals');
        $this->assertCount(2, $shiftCData['needed_volunteer_types']);
        // Has no users
        $entriesC = collect($shiftCData['needed_volunteer_types'])->sortBy('volunteertype.id');
        $entry = $entriesC[0];
        $this->assertCount(0, $entry['entries']);
        $this->assertEquals(3, $entry['needs']);
        // Has users
        $entry = $entriesC[1];
        $this->assertCount(3, $entry['entries']);
        $this->assertEquals(0, $entry['needs']);
        $user = $entry['entries'][0]['user'];
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayNotHasKey('email', $user);
        $this->assertCount(0, $entriesC[0]['entries']);
        $this->assertCount(3, $entriesC[1]['entries']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByVolunteertype
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::getNeededVolunteerTypes
     */
    public function testEntriesByVolunteerType(): void
    {
        /** @var ShiftEntry $firstEntry */
        $firstEntry = $this->shiftB->shiftEntries->first();
        $request = new Request();
        $request = $request->withAttribute('volunteertype_id', $firstEntry->volunteerType->id);

        $controller = new ShiftsController(new Response());

        $response = $controller->entriesByVolunteertype($request);
        $this->validateApiResponse('/volunteertypes/{id}/shifts', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);

        $shift = $data['data'][0];
        $this->assertTrue(count($shift['needed_volunteer_types']) >= 1);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByShiftType
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::shiftEntriesResponse
     */
    public function testEntriesByShiftType(): void
    {
        /** @var ShiftEntry $firstEntry */
        $firstEntry = $this->shiftA->shiftEntries->first();

        $request = new Request();
        $request = $request->withAttribute('shifttype_id', $firstEntry->shift->shift_type_id);

        $controller = new ShiftsController(new Response());

        $response = $controller->entriesByShiftType($request);
        $this->validateApiResponse('/shifttypes/{id}/shifts', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);

        $shift = $data['data'][0];
        $this->assertTrue(count($shift['needed_volunteer_types']) >= 1);

        $freeloaded = [];
        foreach ($shift['needed_volunteer_types'] as $needed) {
            foreach ($needed['entries'] as $entry) {
                if (!$entry['freeloaded_by']) {
                    continue;
                }

                $freeloaded = $entry['freeloaded_by'];
            }
        }

        $this->assertNotEmpty($freeloaded);
        $this->assertEquals(User::first()->id, $freeloaded['id']);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByUser
     */
    public function testEntriesByUser(): void
    {
        /** @var ShiftEntry $firstEntry */
        $firstEntry = $this->shiftA->shiftEntries->first();

        $request = new Request();
        $request = $request->withAttribute('user_id', $firstEntry->user->id);

        $controller = new ShiftsController(new Response());

        $response = $controller->entriesByUser($request);
        $this->validateApiResponse('/users/{id}/shifts', 'get', $response);

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);

        $shift = $data['data'][0];
        $this->assertTrue(count($shift['needed_volunteer_types']) >= 1);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByUser
     */
    public function testEntriesByUserSelf(): void
    {
        $user = User::query()->first();

        $auth = $this->createMock(Authenticator::class);
        $this->setExpects($auth, 'user', null, $user);

        $request = new Request();
        $request = $request->withAttribute('user_id', 'self');

        $controller = new ShiftsController(new Response());
        $controller->setAuth($auth);

        $response = $controller->entriesByUser($request);
        $this->validateApiResponse('/users/{id}/shifts', 'get', $response);
    }

    /**
     * @covers \Volunteersystem\Controllers\Api\ShiftsController::entriesByUser
     */
    public function testEntriesByUserNotFound(): void
    {
        $request = new Request();
        $request = $request->withAttribute('user_id', 42);

        $controller = new ShiftsController(new Response());

        $this->expectException(ModelNotFoundException::class);
        $controller->entriesByUser($request);
    }

    public function setUp(): void
    {
        parent::setUp();

        /*
         * Shift A: Direct shift entries (2 needed)
         * Shift B: Needed by schedule 1 via shift type
         * Shift C: Needed by schedule 2 via location
         */

        $this->location = Location::factory()->create();
        $this->shiftType = ShiftType::factory()->create();
        $this->schedule1 = Schedule::factory()->create([
            'needed_from_shift_type' => true,
            'shift_type' => $this->shiftType->id,
        ]);
        $this->schedule2 = Schedule::factory()->create(['needed_from_shift_type' => false]);

        // Shifts
        $this->shiftA = Shift::factory()->create(['start' => Carbon::now()->subHour()]);
        $this->shiftB = Shift::factory()->create(['start' => Carbon::now()->addHour()]);
        $this->shiftC = Shift::factory()->create([
            'location_id' => $this->location->id,
            'start' => Carbon::now()->addHour(),
        ]);
        $this->shiftD = Shift::factory()->create([
            'start' => Carbon::now()->addHour(),
            'location_id' => $this->location->id,
        ]);

        (new ScheduleShift([
            'shift_id' => $this->shiftB->id,
            'schedule_id' => $this->schedule1->id,
            'guid' => Str::uuid(),
        ]))
            ->save();
        (new ScheduleShift([
            'shift_id' => $this->shiftC->id,
            'schedule_id' => $this->schedule2->id,
            'guid' => Str::uuid(),
        ]))
            ->save();

        // "Empty" entry to be skipped
        NeededVolunteerType::factory(1)
            ->create(['location_id' => null, 'shift_id' => $this->shiftA->id, 'shift_type_id' => null, 'count' => 0]);

        // Needed entry by shift
        /** @var NeededVolunteerType $byShift */
        $byShift = NeededVolunteerType::factory(2)
            ->create(['location_id' => null, 'shift_id' => $this->shiftA->id, 'shift_type_id' => null, 'count' => 2])
            ->first();

        // Needed entry by location via schedule
        /** @var NeededVolunteerType $byLocation */
        $byLocation = NeededVolunteerType::factory()
            ->create(['location_id' => $this->location->id, 'shift_id' => null, 'shift_type_id' => null, 'count' => 3])
            ->first();

        // Needed entry by shift type via schedule
        /** @var NeededVolunteerType $byShiftType */
        $byShiftType = NeededVolunteerType::factory()
            ->create(['location_id' => null, 'shift_id' => null, 'shift_type_id' => $this->shiftType->id, 'count' => 5])
            ->first();

        // By shift
        ShiftEntry::factory(1)->create([
            'shift_id' => $this->shiftA->id,
            'volunteer_type_id' => $byShift->volunteer_type_id,
            'freeloaded_by' => User::first()->id,
        ]);

        // By location via schedule
        ShiftEntry::factory(2)->create([
            'shift_id' => $this->shiftB->id,
            'volunteer_type_id' => $byLocation->volunteer_type_id,
            'freeloaded_by' => null,
        ]);

        // By shift type via schedule
        ShiftEntry::factory(3)->create([
            'shift_id' => $this->shiftC->id,
            'volunteer_type_id' => $byShiftType->volunteer_type_id,
            'freeloaded_by' => null,
        ]);

        // Additional (not required by shift nor location)
        ShiftEntry::factory(5)->create(['shift_id' => $this->shiftA->id, 'freeloaded_by' => null]);

        foreach (User::all() as $user) {
            // Generate user data
            /** @var User $user */
            PersonalData::factory()->create(['user_id' => $user->id]);
            Contact::factory()->create(['user_id' => $user->id]);
        }
    }
}
