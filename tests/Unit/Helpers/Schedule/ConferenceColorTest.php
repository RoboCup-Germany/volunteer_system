<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Volunteersystem\Helpers\Schedule\ConferenceColor;
use Volunteersystem\Test\Unit\TestCase;

class ConferenceColorTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getPrimary
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getBackground
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getOthers
     */
    public function testCreateDefaults(): void
    {
        $conferenceColor = new ConferenceColor();

        $this->assertNull($conferenceColor->getPrimary());
        $this->assertNull($conferenceColor->getBackground());
        $this->assertEmpty($conferenceColor->getOthers());
    }

    /**
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::__construct
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getPrimary
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getBackground
     * @covers \Volunteersystem\Helpers\Schedule\ConferenceColor::getOthers
     */
    public function testCreate(): void
    {
        $conferenceColor = new ConferenceColor(
            '#abcdef',
            '#aabbcc',
            [
                'tertiary' => '#133742',
            ]
        );

        $this->assertEquals('#abcdef', $conferenceColor->getPrimary());
        $this->assertEquals('#aabbcc', $conferenceColor->getBackground());
        $this->assertArrayHasKey('tertiary', $conferenceColor->getOthers());
        $this->assertEquals('#133742', $conferenceColor->getOthers()['tertiary']);
    }
}
