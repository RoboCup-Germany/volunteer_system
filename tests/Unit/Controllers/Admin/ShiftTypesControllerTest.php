<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Admin;

use Volunteersystem\Controllers\Admin\ShiftTypesController;
use Volunteersystem\Events\EventDispatcher;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\Controllers\ControllerTest;
use PHPUnit\Framework\MockObject\MockObject;

class ShiftTypesControllerTest extends ControllerTest
{
    protected Redirector|MockObject $redirect;

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::__construct
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::index
     */
    public function testIndex(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        ShiftType::factory(5)->create();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('admin/shifttypes/index', $view);
                $this->assertTrue($data['is_index'] ?? false);
                $this->assertCount(5, $data['shifttypes'] ?? []);
                return $this->response;
            });

        $controller->index();
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::view
     */
    public function testView(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        /** @var ShiftType $shiftType */
        $shiftType = ShiftType::factory()->create();
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::factory()->create();
        /** @var Shift $shift */
        $shift = Shift::factory()->create(['shift_type_id' => $shiftType->id]);
        NeededVolunteerType::factory()->create(['volunteer_type_id' => $volunteerType->id, 'shift_id' => $shift->id]);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) use ($shift) {
                $this->assertEquals('admin/shifttypes/view', $view);

                $this->assertArrayHasKey('shifttype', $data);
                $this->assertEquals($shift->shiftType->id, $data['shifttype']['id']);

                $this->assertArrayHasKey('days', $data);
                $this->assertArrayHasKey('selected_day', $data);
                $day = $shift->start->format('Y-m-d');
                $this->assertEquals([$day], $data['days']->toArray());
                $this->assertEquals($shift->start->format('Y-m-d'), $data['selected_day']);

                $this->assertArrayHasKey('shifts_active', $data);
                $this->assertFalse($data['shifts_active']);

                $this->assertArrayHasKey('shifts', $data);
                $this->assertEquals($shift->id, $data['shifts']->first()->id);

                return $this->response;
            });

        $controller->view(new Request([], [], ['shift_type_id' => $shiftType->id]));
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::edit
     */
    public function testEdit(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        /** @var ShiftType $shifttype */
        $shifttype = ShiftType::factory()->create();

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) use ($shifttype) {
                $this->assertEquals('admin/shifttypes/edit', $view);
                $this->assertEquals($shifttype->id, $data['shifttype']?->id);
                $this->assertNotEmpty($data['shifttype']?->name);
                $this->assertNotEmpty($data['shifttype']?->description);
                $this->assertNotNull($data['volunteer_types']);
                return $this->response;
            });

        $this->request = $this->request->withAttribute('shift_type_id', 1);

        $controller->edit($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::edit
     */
    public function testEditNew(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);

        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('admin/shifttypes/edit', $view);
                $this->assertArrayHasKey('shifttype', $data);
                $this->assertNull($data['shifttype']);
                $this->assertNotNull($data['volunteer_types']);
                return $this->response;
            });

        $controller->edit($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::save
     */
    public function testSave(): void
    {
        $volunteerType = VolunteerType::factory(2)->create()->first();

        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        $controller->setValidator(new Validator());

        $this->setExpects($this->redirect, 'to', ['/admin/shifttypes']);

        $this->request = $this->request->withParsedBody([
            'name' => 'Test shift type',
            'description' => 'Something',
            'signup_advance_hours' => 42.5,
            'volunteer_type_' . $volunteerType->id => 3,
            'volunteer_type_' . $volunteerType->id + 1 => 0,
        ]);

        $controller->save($this->request);

        $this->assertTrue($this->log->hasInfoThatContains('Saved shift type'));
        $this->assertHasNotification('shifttype.edit.success');
        $this->assertCount(1, ShiftType::whereName('Test shift type')->get());
        $this->assertCount(1, ShiftType::whereDescription('Something')->get());
        $this->assertCount(1, ShiftType::whereSignupAdvanceHours(42.5)->get());
        $this->assertCount(1, ShiftType::first()->neededVolunteerTypes);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::save
     */
    public function testSaveUniqueName(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        $controller->setValidator(new Validator());
        ShiftType::factory()->create(['name' => 'Test shift type']);

        $this->request = $this->request->withParsedBody([
            'name' => 'Test shift type',
        ]);

        $this->expectException(ValidationException::class);
        $controller->save($this->request);
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::save
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::delete
     */
    public function testSaveDelete(): void
    {
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        $controller->setValidator(new Validator());
        /** @var ShiftType $shifttype */
        $shifttype = ShiftType::factory()->create();

        $this->request = $this->request->withParsedBody([
            'id' => '1',
            'delete' => '1',
        ]);

        $controller->save($this->request);
        $this->assertEmpty(ShiftType::find($shifttype->id));
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftTypesController::delete
     */
    public function testDelete(): void
    {
        /** @var EventDispatcher|MockObject $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->app->instance('events.dispatcher', $dispatcher);
        /** @var ShiftTypesController $controller */
        $controller = $this->app->make(ShiftTypesController::class);
        $controller->setValidator(new Validator());
        /** @var ShiftType $shifttype */
        $shifttype = ShiftType::factory()->create();
        /** @var Shift $shift */
        $shift = Shift::factory()->create(['shift_type_id' => $shifttype->id, 'start' => Carbon::create()->subHour()]);
        /** @var User $user */
        $user = User::factory()->create(['name' => 'foo', 'email' => 'lorem@ipsum']);
        /** @var ShiftEntry $shiftEntry */
        ShiftEntry::factory()->create(['shift_id' => $shift->id, 'user_id' => $user->id]);

        $this->setExpects($this->redirect, 'to', ['/admin/shifttypes'], $this->response);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (string $event, array $data) use ($shifttype, $user) {
                $this->assertEquals('shift.deleting', $event);
                $this->assertEquals($shifttype->name, $data['shift']->shiftType->name);
                $this->assertEquals($user->id, $data['shift']->shiftEntries[0]->user->id);

                return [];
            });

        $this->request = $this->request->withParsedBody(['id' => 1, 'delete' => '1']);

        $controller->delete($this->request);

        $this->assertNull(ShiftType::find($shifttype->id));
        $this->assertTrue($this->log->hasInfoThatContains('Deleted shift type'));
        $this->assertHasNotification('shifttype.delete.success');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->redirect = $this->createMock(Redirector::class);
        $this->app->instance(Redirector::class, $this->redirect);
    }
}
