<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\EventRecording;
use Volunteersystem\Test\Unit\TestCase;

class EventRecordingTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::__construct
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getLicense
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::isOptOut
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getUrl
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getLink
     */
    public function testCreateDefaults(): void
    {
        $eventRecording = new EventRecording(
            'WTFPL',
            true
        );

        $this->assertEquals('WTFPL', $eventRecording->getLicense());
        $this->assertTrue($eventRecording->isOptOut());
        $this->assertNull($eventRecording->getUrl());
        $this->assertNull($eventRecording->getLink());
    }

    /**
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::__construct
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getLicense
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::isOptOut
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getUrl
     * @covers \Volunteersystem\Helpers\Schedule\EventRecording::getLink
     */
    public function testCreate(): void
    {
        $eventRecording = new EventRecording(
            'BeerWare',
            false,
            'https://example.com/recording',
            'https://exampple.com/license'
        );

        $this->assertEquals('BeerWare', $eventRecording->getLicense());
        $this->assertFalse($eventRecording->isOptOut());
        $this->assertEquals('https://example.com/recording', $eventRecording->getUrl());
        $this->assertEquals('https://exampple.com/license', $eventRecording->getLink());
    }
}
