<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Illuminate\Database\Eloquent\Model;

class UserVolunteerTypeTest extends ModelTest
{
    protected User|Model $user;

    protected User|Model $confirmed;

    protected VolunteerType|Model $volunteertype;

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType
     */
    public function testCreateDefault(): void
    {
        $model = new UserVolunteerType();
        $model->user()->associate($this->user);
        $model->volunteerType()->associate($this->volunteertype);
        $model->save();

        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);

        $this->assertEquals($this->user->id, $model->user->id);
        $this->assertEquals($this->volunteertype->id, $model->volunteerType->id);
        $this->assertNull($model->confirmUser);
        $this->assertFalse($model->supporter);
    }

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType
     * @covers \Volunteersystem\Models\UserVolunteerType::volunteerType
     * @covers \Volunteersystem\Models\UserVolunteerType::confirmUser
     */
    public function testCreateAssociation(): void
    {
        $this->user
            ->userVolunteerTypes()
            ->attach($this->volunteertype, ['confirm_user_id' => $this->confirmed->id, 'supporter' => true]);

        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);

        $this->assertEquals($this->user->id, $model->user->id);
        $this->assertEquals($this->volunteertype->id, $model->volunteerType->id);
        $this->assertEquals($this->confirmed->id, $model->confirmUser->id);
        $this->assertTrue($model->supporter);
    }

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType::confirmUser
     */
    public function testConfirmUser(): void
    {
        $model = new UserVolunteerType();
        $model->user()->associate($this->user);
        $model->volunteerType()->associate($this->volunteertype);
        $model->confirmUser()->associate($this->confirmed);
        $model->save();

        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);
        $this->assertEquals($this->confirmed->id, $model->confirmUser->id);
    }

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType::volunteerType
     */
    public function testVolunteerType(): void
    {
        $model = new UserVolunteerType();
        $model->user()->associate($this->user);
        $model->volunteerType()->associate($this->volunteertype);
        $model->save();

        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);
        $this->assertEquals($this->volunteertype->id, $model->volunteerType->id);
    }

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType::getPivotAttributes
     */
    public function testGetPivotAttributes(): void
    {
        $attributes = UserVolunteerType::getPivotAttributes();

        $this->assertContains('id', $attributes);
        $this->assertContains('supporter', $attributes);
        $this->assertContains('confirm_user_id', $attributes);
    }

    /**
     * @covers \Volunteersystem\Models\UserVolunteerType::getIsConfirmedAttribute
     */
    public function testGetIsConfirmedAttribute(): void
    {
        $this->volunteertype->restricted = false;
        $this->volunteertype->save();

        $model = new UserVolunteerType();
        $model->user()->associate($this->user);
        $model->volunteerType()->associate($this->volunteertype);
        $model->save();

        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);
        $this->assertTrue($model->isConfirmed);

        $this->volunteertype->restricted = true;
        $this->volunteertype->save();
        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);
        $this->assertFalse($model->isConfirmed);

        $model->confirmUser()->associate($this->confirmed);
        $model->save();
        /** @var UserVolunteerType $model */
        $model = UserVolunteerType::find(1);
        $this->assertTrue($model->isConfirmed);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 42]);
        $this->confirmed = User::factory()->create(['id' => 1337]);
        $this->volunteertype = VolunteerType::factory()->create(['id' => 21]);
    }
}
