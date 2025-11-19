<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Events\Listener;

use Volunteersystem\Config\Config;
use Volunteersystem\Events\Listener\OAuth2;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class OAuth2Test extends TestCase
{
    use HasDatabase;

    /** @var VolunteerType[] */
    protected array $volunteerTypes;

    protected Authenticator | MockObject $auth;

    protected Config $config;

    protected TestLogger $log;

    protected User $user;

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::login
     * @covers \Volunteersystem\Events\Listener\OAuth2::syncTeams
     * @covers \Volunteersystem\Events\Listener\OAuth2::__construct
     */
    public function testLogin(): void
    {
        $this->setExpects($this->auth, 'user', null, $this->user);

        $instance = new OAuth2($this->config, $this->log, $this->auth);
        $instance->login('oauth2.login', 'test-provider', collect(['groups_key' => ['/test', '/lorem']]));

        $user = User::find(1);
        $userVolunteerTypes = $user->userVolunteerTypes;
        $this->assertCount(2, $userVolunteerTypes);
        $this->assertTrue($this->log->hasInfoRecords());

        /** @var VolunteerType $test */
        $test = $userVolunteerTypes->where('pivot.volunteer_type_id', 21)->first();
        $this->assertNotNull($test);
        $this->assertFalse($test->pivot->supporter);
        $this->assertNull($test->pivot->confirm_user_id);
        $this->assertTrue($this->log->hasInfoThatContains('Added to volunteer type'));

        /** @var VolunteerType $lorem */
        $lorem = $userVolunteerTypes->where('pivot.volunteer_type_id', 42)->first();
        $this->assertNotNull($lorem);
        $this->assertTrue($lorem->pivot->supporter);
        $this->assertEquals($user->id, $lorem->pivot->confirm_user_id);
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::login
     */
    public function testLoginNoProvider(): void
    {
        $this->setExpects($this->auth, 'user', null, $this->user);

        $instance = new OAuth2($this->config, $this->log, $this->auth);
        $instance->login('oauth2.login', 'unavailable-provider', collect(['foo' => 'bar']));
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::login
     */
    public function testLoginNoMatchingGroups(): void
    {
        $this->setExpects($this->auth, 'user', null, $this->user);

        $instance = new OAuth2($this->config, $this->log, $this->auth);
        $instance->login('oauth2.login', 'test-provider', collect(['groups_key' => ['/notMatching']]));
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::login
     * @covers \Volunteersystem\Events\Listener\OAuth2::syncTeams
     */
    public function testLoginNoChanges(): void
    {
        $this->setExpects($this->auth, 'user', null, $this->user);
        $this->user->userVolunteerTypes()->attach($this->volunteerTypes['test']);
        $this->user->userVolunteerTypes()->attach(
            $this->volunteerTypes['lorem'],
            ['supporter' => true, 'confirm_user_id' => $this->user->id]
        );

        $instance = new OAuth2($this->config, $this->log, $this->auth);
        $instance->login('oauth2.login', 'test-provider', collect(['groups_key' => ['/test', '/lorem']]));

        /** @var UserVolunteerType $test */
        $test = UserVolunteerType::find(1);
        $this->assertFalse($test->supporter);
        $this->assertNull($test->confirm_user_id);

        /** @var UserVolunteerType $test */
        $lorem = UserVolunteerType::find(2);
        $this->assertTrue($lorem->supporter);
        $this->assertEquals($this->user->id, $lorem->confirm_user_id);

        $this->assertEmpty($this->log->records);
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::login
     * @covers \Volunteersystem\Events\Listener\OAuth2::syncTeams
     */
    public function testLoginChangeSupport(): void
    {
        $this->setExpects($this->auth, 'user', null, $this->user);
        $this->user->userVolunteerTypes()->attach($this->volunteerTypes['test']);
        $this->user->userVolunteerTypes()->attach($this->volunteerTypes['lorem']);

        $instance = new OAuth2($this->config, $this->log, $this->auth);
        $instance->login('oauth2.login', 'test-provider', collect(['groups_key' => ['/lorem', '/test']]));

        /** @var UserVolunteerType $userVolunteerType */
        $userVolunteerType = UserVolunteerType::find(2);
        $this->assertTrue($userVolunteerType->supporter);
        $this->assertEquals($this->user->id, $userVolunteerType->confirm_user_id);

        $this->assertTrue($this->log->hasInfoThatContains('supporter'));
        $this->assertTrue($this->log->hasInfoThatContains('confirmed'));
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::getSsoTeams
     */
    public function testGetSsoTeamsNotConfigured(): void
    {
        $instance = new OAuth2($this->config, $this->log, $this->auth);

        $teams = $instance->getSsoTeams('NotExistentProvider');
        $this->assertEquals([], $teams);
    }

    /**
     * @covers \Volunteersystem\Events\Listener\OAuth2::getSsoTeams
     */
    public function testGetSsoTeams(): void
    {
        $instance = new OAuth2($this->config, $this->log, $this->auth);

        $teams = $instance->getSsoTeams('test-provider');
        $this->assertEquals([
            '/test'  => ['id' => 21, 'supporter' => false],
            '/lorem' => ['id' => 42, 'supporter' => true],
        ], $teams);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDatabase();

        $this->config = new Config(['oauth' => [
            'test-provider' => [
                'groups' => 'groups_key',
                'teams'  => [
                    '/test'  => 21,
                    '/lorem' => ['id' => 42, 'supporter' => true],
                ],
            ],
        ]]);
        $this->app->instance(Config::class, $this->config);

        $this->log = new TestLogger();
        $this->app->instance(LoggerInterface::class, $this->log);

        $this->auth = $this->createMock(Authenticator::class);

        /** @var User $user */
        $user = User::factory()->create();
        $this->user = $user;

        /** @var VolunteerType $volunteerType1 */
        $volunteerType1 = VolunteerType::factory()->create(['id' => 21, 'name' => 'Test Name']);
        $this->volunteerTypes['test'] = $volunteerType1;

        /** @var VolunteerType $volunteerType2 */
        $volunteerType2 = VolunteerType::factory()->create(['id' => 42, 'name' => 'Lorem Name']);
        $this->volunteerTypes['lorem'] = $volunteerType2;
    }
}
