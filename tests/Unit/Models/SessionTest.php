<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Models\Session;
use Volunteersystem\Models\User\User;

/**
 * This class provides tests for the Session model
 */
class SessionTest extends ModelTest
{
    /**
     * Tests that a Session can be created and loaded
     *
     * @covers \Volunteersystem\Models\Session
     */
    public function testCreate(): void
    {
        $user = User::factory()->create();
        Session::create([
            'id' => 'foo',
            'payload' => 'lorem ipsum',
            'user_id' => $user->id,
            'last_activity' => Carbon::now(),
        ]);
        Session::create([
            'id' => 'bar',
            'last_activity' => Carbon::now(),
        ]);

        $session = Session::find('foo');
        $this->assertNotNull($session);
        $this->assertEquals('lorem ipsum', $session->payload);
        $this->assertInstanceOf(User::class, $session->user);

        $session = Session::find('bar');
        $this->assertNull($session->user);
    }
}
