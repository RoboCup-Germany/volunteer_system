<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Config\Config;
use Volunteersystem\Helpers\Goodie;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\Worklog;
use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class GoodieTest extends TestCase
{
    use HasDatabase;

    /**
     * @covers \Volunteersystem\Helpers\Goodie::shiftScoreQuery
     */
    public function testShiftScoreQuery(): void
    {
        $result = Goodie::shiftScoreQuery();

        $this->assertEquals('0', $result->getValue(new SQLiteGrammar()));
    }

    /**
     * @covers \Volunteersystem\Helpers\Goodie::userScore
     * @covers \Volunteersystem\Helpers\Goodie::worklogScoreQuery
     */
    public function testUserScore(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Worklog::factory()->create(['user_id' => $user->id, 'hours' => 42.23]);

        $result = Goodie::userScore($user);

        $this->assertEquals(42.23, $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->initDatabase();
        $this->app->instance('config', new Config(['night_shifts' => ['enabled' => false]]));
    }
}
