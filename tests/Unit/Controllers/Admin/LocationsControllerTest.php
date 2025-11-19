<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Admin;

use Volunteersystem\Controllers\Admin\LocationsController;
use Volunteersystem\Events\EventDispatcher;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Controllers\ControllerTest;
use PHPUnit\Framework\MockObject\MockObject;

class LocationsControllerTest extends ControllerTest
{
    protected Redirector|MockObject $redirect;

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::__construct
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::index
     */
    public function testIndex(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        Location::factory(5)->create();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('pages/locations/index', $view);
                $this->assertTrue($data['is_index'] ?? false);
                $this->assertCount(5, $data['locations'] ?? []);
                return $this->response;
            });

        $controller->index();
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::edit
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::showEdit
     */
    public function testEdit(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        /** @var Location $location */
        $location = Location::factory()->create();
        $volunteerTypes = VolunteerType::factory(3)->create();
        (new NeededVolunteerType([
            'location_id' => $location->id,
            'volunteer_type_id' => $volunteerTypes[0]->id,
            'count' => 3,
        ]))->save();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) use ($location) {
                $this->assertEquals('admin/locations/edit', $view);
                $this->assertEquals($location->id, $data['location']?->id);
                $this->assertCount(3, $data['volunteer_types'] ?? []);
                $this->assertCount(1, $data['needed_volunteer_types'] ?? []);
                return $this->response;
            });

        $this->request = $this->request->withAttribute('location_id', 1);

        $controller->edit($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::edit
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::showEdit
     */
    public function testEditNew(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        VolunteerType::factory(3)->create();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('admin/locations/edit', $view);
                $this->assertEmpty($data['location'] ?? []);
                $this->assertCount(3, $data['volunteer_types'] ?? []);
                $this->assertEmpty($data['needed_volunteer_types'] ?? []);
                return $this->response;
            });

        $controller->edit($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::save
     */
    public function testSave(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        $controller->setValidator(new Validator());
        VolunteerType::factory(3)->create();

        $this->setExpects($this->redirect, 'to', ['/locations']);

        $this->request = $this->request->withParsedBody([
            'name'         => 'Testlocation',
            'description'  => 'Something',
            'dect'         => 'DECTNR',
            'map_url'      => 'https://osm.url/#map=h/x/y',
            'volunteer_type_1' => '0',
            'volunteer_type_2' => '3',
        ]);

        $controller->save($this->request);

        $this->assertTrue($this->log->hasInfoThatContains('Updated location'));
        $this->assertHasNotification('location.edit.success');
        $this->assertCount(1, Location::whereName('Testlocation')->get());

        $neededVolunteerType = NeededVolunteerType::whereLocationId(1)
            ->where('volunteer_type_id', 2)
            ->where('count', 3)
            ->get();
        $this->assertCount(1, $neededVolunteerType);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::save
     */
    public function testSaveUniqueName(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        $controller->setValidator(new Validator());
        Location::factory()->create(['name' => 'Testlocation']);

        $this->request = $this->request->withParsedBody([
            'name' => 'Testlocation',
        ]);

        $this->expectException(ValidationException::class);
        $controller->save($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::save
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::delete
     */
    public function testSaveDelete(): void
    {
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        $controller->setValidator(new Validator());
        /** @var Location $location */
        $location = Location::factory()->create();

        $this->request = $this->request->withParsedBody([
            'id'     => '1',
            'delete' => '1',
        ]);

        $controller->save($this->request);
        $this->assertEmpty(Location::find($location->id));
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\LocationsController::delete
     */
    public function testDelete(): void
    {
        /** @var EventDispatcher|MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->app->instance('events.dispatcher', $dispatcher);
        /** @var LocationsController $controller */
        $controller = $this->app->make(LocationsController::class);
        $controller->setValidator(new Validator());
        /** @var Location $location */
        $location = Location::factory()->create();
        /** @var Shift $shift */
        $shift = Shift::factory()->create(['location_id' => $location->id, 'start' => Carbon::create()->subHour()]);
        /** @var User $user */
        $user = User::factory()->create(['name' => 'foo', 'email' => 'lorem@ipsum']);
        /** @var ShiftEntry $shiftEntry */
        ShiftEntry::factory()->create(['shift_id' => $shift->id, 'user_id' => $user->id]);

        $this->setExpects($this->redirect, 'to', ['/locations'], $this->response);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (string $event, array $data) use ($location, $user) {
                $this->assertEquals('shift.deleting', $event);
                $this->assertEquals($location->id, $data['shift']->location->id);
                $this->assertEquals($user->id, $data['shift']->shiftEntries[0]->user->id);

                return [];
            });

        $this->request = $this->request->withParsedBody(['id' => 1, 'delete' => '1']);

        $controller->delete($this->request);

        $this->assertNull(Location::find($location->id));
        $this->assertTrue($this->log->hasInfoThatContains('Deleted location'));
        $this->assertHasNotification('location.delete.success');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->redirect = $this->createMock(Redirector::class);
        $this->app->instance(Redirector::class, $this->redirect);
    }
}
