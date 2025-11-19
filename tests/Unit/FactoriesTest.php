<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Faq;
use Volunteersystem\Models\Group;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\LogEntry;
use Volunteersystem\Models\Message;
use Volunteersystem\Models\News;
use Volunteersystem\Models\NewsComment;
use Volunteersystem\Models\OAuth;
use Volunteersystem\Models\Privilege;
use Volunteersystem\Models\Question;
use Volunteersystem\Models\Session;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\Tag;
use Volunteersystem\Models\User\Contact;
use Volunteersystem\Models\User\License;
use Volunteersystem\Models\User\PasswordReset;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\Settings;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\Models\Worklog;
use Illuminate\Database\Eloquent\Model;

class FactoriesTest extends TestCase
{
    use HasDatabase;

    /**
     * @return string[][]
     */
    public function factoriesProvider(): array
    {
        return [
            [VolunteerType::class],
            [Contact::class],
            [Faq::class],
            [Group::class],
            [License::class],
            [Location::class],
            [LogEntry::class],
            [Message::class],
            [NeededVolunteerType::class],
            [News::class],
            [NewsComment::class],
            [OAuth::class],
            [PasswordReset::class],
            [PersonalData::class],
            [Privilege::class],
            [Question::class],
            [Schedule::class],
            [Session::class],
            [Settings::class],
            [Shift::class],
            [ShiftEntry::class],
            [ShiftType::class],
            [State::class],
            [Tag::class],
            [UserVolunteerType::class],
            [User::class],
            [Worklog::class],
        ];
    }

    /**
     * Test all model factories
     *
     * @covers       \Database\Factories\Volunteersystem\Models\VolunteerTypeFactory
     * @covers       \Database\Factories\Volunteersystem\Models\FaqFactory
     * @covers       \Database\Factories\Volunteersystem\Models\GroupFactory
     * @covers       \Database\Factories\Volunteersystem\Models\LocationFactory
     * @covers       \Database\Factories\Volunteersystem\Models\LogEntryFactory
     * @covers       \Database\Factories\Volunteersystem\Models\MessageFactory
     * @covers       \Database\Factories\Volunteersystem\Models\NewsCommentFactory
     * @covers       \Database\Factories\Volunteersystem\Models\NewsFactory
     * @covers       \Database\Factories\Volunteersystem\Models\OAuthFactory
     * @covers       \Database\Factories\Volunteersystem\Models\PrivilegeFactory
     * @covers       \Database\Factories\Volunteersystem\Models\QuestionFactory
     * @covers       \Database\Factories\Volunteersystem\Models\SessionFactory
     * @covers       \Database\Factories\Volunteersystem\Models\Shifts\NeededVolunteerTypeFactory
     * @covers       \Database\Factories\Volunteersystem\Models\Shifts\ScheduleFactory
     * @covers       \Database\Factories\Volunteersystem\Models\Shifts\ShiftEntryFactory
     * @covers       \Database\Factories\Volunteersystem\Models\Shifts\ShiftFactory
     * @covers       \Database\Factories\Volunteersystem\Models\Shifts\ShiftTypeFactory
     * @covers       \Database\Factories\Volunteersystem\Models\TagFactory
     * @covers       \Database\Factories\Volunteersystem\Models\UserVolunteerTypeFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\ContactFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\LicenseFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\PasswordResetFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\PersonalDataFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\SettingsFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\StateFactory
     * @covers       \Database\Factories\Volunteersystem\Models\User\UserFactory
     * @covers       \Database\Factories\Volunteersystem\Models\WorklogFactory
     *
     * @dataProvider factoriesProvider
     */
    public function testFactories(string $model): void
    {
        $this->initDatabase();

        $instance = (new $model())->factory()->create();
        $this->assertInstanceOf(Model::class, $instance);
    }

    /**
     * @covers \Database\Factories\Volunteersystem\Models\User\StateFactory
     */
    public function testStateFactoryArrived(): void
    {
        $this->initDatabase();

        /** @var State $instance */
        $instance = (new State())->factory()->arrived()->create();
        $this->assertInstanceOf(Model::class, $instance);
        $this->assertTrue($instance->arrived);
    }
}
