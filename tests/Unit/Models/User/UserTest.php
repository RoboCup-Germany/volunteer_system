<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models\User;

use Carbon\Carbon;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Volunteersystem\Config\Config;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\Group;
use Volunteersystem\Models\LogEntry;
use Volunteersystem\Models\News;
use Volunteersystem\Models\NewsComment;
use Volunteersystem\Models\OAuth;
use Volunteersystem\Models\Privilege;
use Volunteersystem\Models\Question;
use Volunteersystem\Models\Session;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\Contact;
use Volunteersystem\Models\User\HasUserModel;
use Volunteersystem\Models\User\License;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\Settings;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\Models\Worklog;
use Volunteersystem\Test\Unit\Models\ModelTest;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UserTest extends ModelTest
{
    use ArraySubsetAsserts;

    /** @var string[] */
    protected array $data = [
        'name'     => 'lorem',
        'password' => '',
        'email'    => 'foo@bar.batz',
        'api_key'  => '',
    ];

    public function hasOneRelationsProvider(): array
    {
        return [
            [
                Contact::class,
                'contact',
                [
                    'dect'   => '1234567',
                    'email'  => 'foo@bar.batz',
                    'mobile' => '1234/12341234',
                ],
            ],
            [
                PersonalData::class,
                'personalData',
                [
                    'first_name' => 'Foo',
                ],
            ],
            [
                Settings::class,
                'settings',
                [
                    'language' => 'de_DE',
                    'theme'    => 1,
                ],
            ],
            [
                State::class,
                'state',
                [
                    'force_active' => true,
                    'force_food' => true,
                ],
            ],
            [
                License::class,
                'license',
                [
                    'has_car'   => true,
                    'drive_car' => true,
                ],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function hasManyRelationsProvider(): array
    {
        return [
            'news' => [
                News::class,
                'news',
                [
                    [
                        'title'      => 'Hey hoo',
                        'text'       => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                        'is_meeting' => false,
                    ],
                    [
                        'title'      => 'Huuhuuu',
                        'text'       => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit.',
                        'is_meeting' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function belongsToManyRelationsProvider(): array
    {
        return [
            'groups' => [
                Group::class,
                'groups',
                [
                    [
                        'name' => 'Lorem',
                    ],
                    [
                        'name' => 'Ipsum',
                    ],
                ],
            ],
        ];
    }

    /**
     * @covers       \Volunteersystem\Models\User\User::contact
     * @covers       \Volunteersystem\Models\User\User::license
     * @covers       \Volunteersystem\Models\User\User::personalData
     * @covers       \Volunteersystem\Models\User\User::settings
     * @covers       \Volunteersystem\Models\User\User::state
     *
     * @dataProvider hasOneRelationsProvider
     *
     * @throws Exception
     */
    public function testHasOneRelations(string $class, string $name, array $data): void
    {
        $user = new User($this->data);
        $user->save();

        /** @var HasUserModel $instance */
        $instance = new $class($data);
        $instance->user()
            ->associate($user)
            ->save();

        $this->assertArraySubset($data, (array) $user->{$name}->attributesToArray());
    }

    /**
     * @covers       \Volunteersystem\Models\User\User::news()
     *
     * @dataProvider hasManyRelationsProvider
     *
     * @param string $class Class name of the related models
     * @param string $name Name of the accessor for the related models
     * @param array  $modelData List of the related models
     */
    public function testHasManyRelations(string $class, string $name, array $modelData): void
    {
        $user = new User($this->data);
        $user->save();

        $relatedModelIds = [];

        foreach ($modelData as $data) {
            /** @var BaseModel $model */
            $model = $this->app->make($class);
            $stored = $model->create($data + ['user_id' => $user->id]);
            $relatedModelIds[] = $stored->id;
        }

        $this->assertEquals($relatedModelIds, $user->{$name}->modelKeys());
    }


    /**
     * @covers       \Volunteersystem\Models\User\User::groups
     *
     * @dataProvider belongsToManyRelationsProvider
     *
     * @param string $class Class name of the related models
     * @param string $name Name of the accessor for the related models
     * @param array  $modelData List of the related models
     */
    public function testBelongsToManyRelations(string $class, string $name, array $modelData): void
    {
        $user = new User($this->data);
        $user->save();

        $relatedModelIds = [];

        foreach ($modelData as $data) {
            /** @var BaseModel $model */
            $model = $this->app->make($class);
            $stored = $model->create($data);
            $stored->users()->attach($user);
            $relatedModelIds[] = $stored->id;
        }

        $this->assertEquals($relatedModelIds, $user->{$name}->modelKeys());
    }

    /**
     * @covers \Volunteersystem\Models\User\User::isFreeloader
     */
    public function testIsFreeloader(): void
    {
        $this->app->instance('config', new Config([
            'max_freeloadable_shifts' => 2,
        ]));

        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var User $user3 */
        $user3 = User::factory()->create();

        $userSource = new User($this->data);
        $userSource->save();
        $this->assertFalse($userSource->isFreeloader());

        ShiftEntry::factory()->create(['user_id' => $userSource->id, 'freeloaded_by' => null]);
        ShiftEntry::factory()->create(['user_id' => $userSource->id, 'freeloaded_by' => $user1->id]);
        $this->assertFalse($userSource->isFreeloader());

        ShiftEntry::factory()->create(['user_id' => $userSource->id, 'freeloaded_by' => $user2->id]);
        $this->assertTrue($userSource->isFreeloader());

        ShiftEntry::factory()->create(['user_id' => $userSource->id, 'freeloaded_by' => $user3->id]);
        $this->assertTrue($userSource->isFreeloader());
    }

    /**
     * @covers \Volunteersystem\Models\User\User::siftEntriesMarkedFreeloaded
     */
    public function testSiftEntriesMarkedFreeloaded(): void
    {
        /** @var User $freeloader */
        $freeloader = User::factory()->create();

        $user = new User($this->data);
        $user->save();

        ShiftEntry::factory(2)->create(['user_id' => $freeloader->id, 'freeloaded_by' => $user->id]);

        $this->assertCount(2, $user->siftEntriesMarkedFreeloaded);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::userVolunteerTypes
     */
    public function testUserVolunteerTypes(): void
    {
        VolunteerType::factory(2)->create();
        $volunteerType1 = VolunteerType::factory()->create();
        VolunteerType::factory(1)->create();
        $volunteerType2 = VolunteerType::factory()->create();

        $user = new User($this->data);
        $user->save();

        $user->userVolunteerTypes()->attach($volunteerType1);
        $user->userVolunteerTypes()->attach($volunteerType2);

        /** @var UserVolunteerType $userVolunteerType */
        $userVolunteerType = UserVolunteerType::find(1);
        $this->assertEquals($user->id, $userVolunteerType->user->id);

        $volunteertypes = $user->userVolunteerTypes;
        $this->assertCount(2, $volunteertypes);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::isVolunteerTypeSupporter
     */
    public function testIsVolunteerTypeSupporter(): void
    {
        /** @var VolunteerType $volunteerType1 */
        $volunteerType1 = VolunteerType::factory()->create();
        /** @var VolunteerType $volunteerType2 */
        $volunteerType2 = VolunteerType::factory()->create();

        $user = new User($this->data);
        $user->save();

        $user->userVolunteerTypes()->attach($volunteerType1, ['supporter' => true]);
        $user->userVolunteerTypes()->attach($volunteerType2);

        $this->assertTrue($user->isVolunteerTypeSupporter($volunteerType1));
        $this->assertFalse($user->isVolunteerTypeSupporter($volunteerType2));
    }

    /**
     * @covers \Volunteersystem\Models\User\User::logs
     */
    public function testLogs(): void
    {
        $user = new User($this->data);
        $user->save();

        LogEntry::factory(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->logs);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::privileges
     * @covers \Volunteersystem\Models\User\User::getPrivilegesAttribute
     */
    public function testPrivileges(): void
    {
        $user = new User($this->data);
        $user->save();

        /** @var Group $group1 */
        $group1 = Group::factory()->create();
        /** @var Group $group2 */
        $group2 = Group::factory()->create();
        /** @var Group $group3 */
        $group3 = Group::factory()->create();
        /** @var Privilege $privilege1 */
        $privilege1 = Privilege::factory()->create();
        /** @var Privilege $privilege2 */
        $privilege2 = Privilege::factory()->create();
        /** @var Privilege $privilege3 */
        $privilege3 = Privilege::factory()->create();
        /** @var Privilege $privilege4 */
        $privilege4 = Privilege::factory()->create();

        $user->groups()->attach($group1);
        $user->groups()->attach($group2);

        $group1->privileges()->attach($privilege1);
        $group1->privileges()->attach($privilege2);

        $group2->privileges()->attach($privilege2);
        $group2->privileges()->attach($privilege3);

        $group3->privileges()->attach($privilege3);
        $group3->privileges()->attach($privilege4);

        /** @var User $createdUser */
        $createdUser = User::first();
        $this->assertInstanceOf(Builder::class, $createdUser->privileges());

        $privileges = $createdUser->privileges->pluck('name');
        $this->assertCount(3, $privileges);
        $this->assertContains($privilege1->name, $privileges);
        $this->assertContains($privilege2->name, $privileges);
        $this->assertContains($privilege3->name, $privileges);
    }

    /**
     * Tests that accessing the NewsComments of an User works.
     *
     * @covers \Volunteersystem\Models\User\User::newsComments
     */
    public function testNewsComments(): void
    {
        News::factory()->create();
        ($user = new User($this->data))->save();
        $newsComment = NewsComment::create(['news_id' => 1, 'text' => 'test comment', 'user_id' => $user->id]);
        $comments = $user->newsComments;

        $this->assertCount(1, $comments);
        $comment = $comments->first();
        $this->assertSame($newsComment->id, $comment->id);
    }

    /**
     * Tests that accessing OAuth of an User works
     *
     * @covers \Volunteersystem\Models\User\User::oauth
     */
    public function testOauth(): void
    {
        ($user = new User($this->data))->save();
        (new OAuth(['provider' => 'test', 'identifier' => 'LoremIpsumDolor123']))
            ->user()
            ->associate($user)
            ->save();

        $oauth = $user->oauth;

        $this->assertCount(1, $oauth);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::shiftEntries
     */
    public function testShiftEntries(): void
    {
        $user = new User($this->data);
        $user->save();

        ShiftEntry::factory(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->shiftEntries);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::sessions
     */
    public function testSessions(): void
    {
        $user = new User($this->data);
        $user->save();

        Session::factory(2)->create();
        Session::factory(3)->create(['user_id' => $user->id]);
        Session::factory(2)->create();
        Session::factory(4)->create(['user_id' => $user->id]);

        $this->assertCount(7, $user->sessions);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::worklogs
     */
    public function testWorklogs(): void
    {
        ($user = new User($this->data))->save();
        $worklogEntry = Worklog::create([
            'user_id'     => $user->id,
            'creator_id'  => $user->id,
            'hours'       => 1,
            'description' => '',
            'worked_at'   => Carbon::now(),
        ]);

        $worklogs = $user->worklogs;
        $this->assertCount(1, $worklogs);
        $worklog = $worklogs->first();
        $this->assertSame($worklogEntry->id, $worklog->id);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::worklogsCreated
     */
    public function testWorklogsCreated(): void
    {
        ($user = new User($this->data))->save();
        $worklogEntry = Worklog::create([
            'user_id'     => $user->id,
            'creator_id'  => $user->id,
            'hours'       => 1,
            'description' => '',
            'worked_at'   => Carbon::now(),
        ]);

        $worklogs = $user->worklogsCreated;
        $this->assertCount(1, $worklogs);
        $worklog = $worklogs->first();
        $this->assertSame($worklogEntry->id, $worklog->id);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::questionsAsked
     */
    public function testQuestionsAsked(): void
    {
        ($user = new User($this->data))->save();
        ($user2 = new User(array_merge($this->data, ['name' => 'dolor', 'email' => 'dolor@bar.batz'])))->save();

        ($question1 = new Question(['user_id' => $user->id, 'text' => Str::random()]))->save();
        ($question2 = new Question(['user_id' => $user->id, 'text' => Str::random()]))->save();
        // create some questions asked by user 2 to test the correct assignment
        (new Question(['user_id' => $user2->id, 'text' => Str::random()]))->save();
        (new Question(['user_id' => $user2->id, 'text' => Str::random()]))->save();

        $questionIds = $user->questionsAsked()->pluck('id')->toArray();
        $this->assertCount(2, $questionIds);
        $this->assertContains($question1->id, $questionIds);
        $this->assertContains($question2->id, $questionIds);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::questionsAnswered
     */
    public function testQuestionsAnswered(): void
    {
        ($user = new User($this->data))->save();
        ($user2 = new User(array_merge($this->data, ['name' => 'dolor', 'email' => 'dolor@bar.batz'])))->save();

        $questionData = ['user_id' => $user->id, 'text' => Str::random()];
        $answerData = ['answerer_id' => $user2->id, 'answer' => Str::random()];
        ($question1 = new Question(array_merge($questionData, $answerData)))->save();
        ($question2 = new Question(array_merge($questionData, $answerData)))->save();
        // Create some questions asked by user 2 to test the correct assignment
        (new Question(array_merge($questionData, $answerData, ['answerer_id' => $user->id])))->save();
        (new Question($questionData))->save();
        (new Question($questionData))->save();

        $answers = $user2->questionsAnswered()->pluck('id')->toArray();
        $this->assertCount(2, $answers);
        $this->assertContains($question1->id, $answers);
        $this->assertContains($question2->id, $answers);
    }

    /**
     * @covers \Volunteersystem\Models\User\User::shiftsCreated
     * @covers \Volunteersystem\Models\User\User::shiftsUpdated
     */
    public function testShiftsCreatedAndUpdated(): void
    {
        ($user1 = new User($this->data))->save();
        ($user2 = new User(array_merge($this->data, ['name' => 'foo', 'email' => 'someone@bar.batz'])))->save();

        Shift::factory()->create();
        Shift::factory()->create(['created_by' => $user1->id]);
        Shift::factory()->create(['created_by' => $user2->id, 'updated_by' => $user1->id]);
        Shift::factory()->create(['created_by' => $user1->id, 'updated_by' => $user2->id]);
        Shift::factory()->create(['created_by' => $user2->id, 'updated_by' => $user2->id]);
        Shift::factory()->create(['created_by' => $user1->id]);

        $user1 = User::find(1);
        $this->assertCount(3, $user1->shiftsCreated);
        $this->assertCount(1, $user1->shiftsUpdated);

        $user2 = User::find(2);
        $this->assertCount(2, $user2->shiftsCreated);
        $this->assertCount(2, $user2->shiftsUpdated);
    }

    public function getDisplayNameAttributeProvider(): array
    {
        return [
            ['lorem'],
            ['lorem', ' '],
            ['lorem', null, ' '],
            ['lorem', ' ', ' '],
            ['Test', 'Test', ' '],
            ['Tester', ' ', 'Tester'],
            ['Foo', 'Foo'],
            ['Bar', null, 'Bar'],
            ['Foo Bar', 'Foo', 'Bar'],
            ['Some name', ' Some', ' name'],
            ['Another Surname', ' Another ', ' Surname '],
        ];
    }

    /**
     * @covers       \Volunteersystem\Models\User\User::getDisplayNameAttribute
     * @dataProvider getDisplayNameAttributeProvider
     */
    public function testGetDisplayNameAttribute(
        string $expected,
        ?string $firstName = null,
        ?string $lastName = null
    ): void {
        $this->app->instance('config', new Config());

        ($user1 = new User($this->data))->save();
        $user1->personalData->first_name = $firstName;
        $user1->personalData->last_name = $lastName;

        $this->assertEquals('lorem', $user1->displayName);

        config(['display_full_name' => true]);
        $this->assertEquals($expected, $user1->displayName);
    }
}
