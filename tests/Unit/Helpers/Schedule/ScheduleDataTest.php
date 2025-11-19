<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\ScheduleData;
use Volunteersystem\Test\Unit\TestCase;

class ScheduleDataTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\ScheduleData::patch
     */
    public function testPatch(): void
    {
        $instance = new class ('value') extends ScheduleData {
            public function __construct(
                protected string $key
            ) {
            }

            public function getKey(): string
            {
                return $this->key;
            }
        };

        $this->assertEquals('value', $instance->getKey());

        $instance->patch('key', 'new');
        $this->assertEquals('new', $instance->getKey());
    }
}
