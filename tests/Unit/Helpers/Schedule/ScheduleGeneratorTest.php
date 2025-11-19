<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\ScheduleGenerator;
use Volunteersystem\Test\Unit\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::getName
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::getVersion
     */
    public function testCreateDefaults(): void
    {
        $conferenceColor = new ScheduleGenerator();

        $this->assertNull($conferenceColor->getName());
        $this->assertNull($conferenceColor->getVersion());
    }

    /**
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::getName
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleGenerator::getVersion
     */
    public function testCreate(): void
    {
        $conferenceColor = new ScheduleGenerator('Volunteersystem', '1.2.3');

        $this->assertEquals('Volunteersystem', $conferenceColor->getName());
        $this->assertEquals('1.2.3', $conferenceColor->getVersion());
    }
}
