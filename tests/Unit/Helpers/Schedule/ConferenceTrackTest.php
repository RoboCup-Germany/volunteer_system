<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\ConferenceTrack;
use Volunteersystem\Test\Unit\TestCase;

class ConferenceTrackTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getName
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getColor
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getSlug
     */
    public function testCreateDefaults(): void
    {
        $conferenceColor = new ConferenceTrack('Tracking');

        $this->assertEquals('Tracking', $conferenceColor->getName());
        $this->assertNull($conferenceColor->getColor());
        $this->assertNull($conferenceColor->getSlug());
    }

    /**
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getName
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getColor
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceTrack::getSlug
     */
    public function testCreate(): void
    {
        $conferenceColor = new ConferenceTrack(
            'Testing',
            '#abcdef',
            'testing'
        );

        $this->assertEquals('Testing', $conferenceColor->getName());
        $this->assertEquals('#abcdef', $conferenceColor->getColor());
        $this->assertEquals('testing', $conferenceColor->getSlug());
    }
}
