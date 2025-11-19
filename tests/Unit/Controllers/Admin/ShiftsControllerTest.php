<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Admin;

use Volunteersystem\Controllers\Admin\ShiftsController;
use Volunteersystem\Events\EventDispatcher;
use Volunteersystem\Helpers\Uuid;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Request;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Test\Unit\Controllers\ControllerTest;
use PHPUnit\Framework\MockObject\MockObject;

class ShiftsControllerTest extends ControllerTest
{
    protected Redirector|MockObject $redirect;

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftsController::__construct
     * @covers \Volunteersystem\Controllers\Admin\ShiftsController::history
     */
    public function testHistory(): void
    {
        $this->response->expects($this->once())
            ->method('withView')
            ->willReturnCallback(function (string $view, array $data) {
                $this->assertEquals('admin/shifts/history', $view);
                $this->assertCount(2, $data['shifts'] ?? []);
                return $this->response;
            });

        /** @var ShiftsController $controller */
        $controller = $this->app->make(ShiftsController::class);
        $controller->history();
    }

    /**
     * @covers \Volunteersystem\Controllers\Admin\ShiftsController::deleteTransaction
     */
    public function testDeleteTransaction(): void
    {
        $this->database->getConnection()->getRawPdo()->exec('PRAGMA foreign_keys = ON');

        $this->redirect->expects($this->once())
            ->method('back')
            ->willReturn($this->response);
        /** @var Shift $shift */
        $shift = Shift::factory(3)->create(['transaction_id' => Uuid::uuid()])->last();
        ShiftEntry::factory(2)->create(['shift_id' => $shift->id]);

        /** @var EventDispatcher|MockObject $event */
        $event = $this->createMock(EventDispatcher::class);
        $this->app->instance('events.dispatcher', $event);
        $this->setExpects($event, 'dispatch', ['shift.deleting'], [], $this->exactly(3));

        /** @var ShiftsController $controller */
        $controller = $this->app->make(ShiftsController::class);
        $controller->deleteTransaction(new Request([], ['transaction_id' => $shift->transaction_id]));

        $this->assertCount(6, Shift::all());
        $this->assertCount(3, ShiftEntry::all());
        $this->log->hasInfoThatContains('Deleted shift');
        $this->log->hasInfoThatContains('shifts with transaction ID');
        $this->assertHasNotification('shifts.history.delete.success');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->redirect = $this->createMock(Redirector::class);
        $this->app->instance(Redirector::class, $this->redirect);

        Shift::factory(1)->create(['transaction_id' => null]);
        Shift::factory(4)->create(['transaction_id' => Uuid::uuid()]);
        $shift = Shift::factory(1)->create(['transaction_id' => Uuid::uuid()])->first();

        ShiftEntry::factory(3)->create(['shift_id' => $shift->id]);
    }
}
