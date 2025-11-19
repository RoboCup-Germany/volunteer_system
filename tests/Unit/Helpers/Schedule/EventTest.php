<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers\Schedule;

use Carbon\Carbon;
use Volunteersystem\Helpers\Schedule\ConferenceTrack;
use Volunteersystem\Helpers\Schedule\Event;
use Volunteersystem\Helpers\Schedule\EventRecording;
use Volunteersystem\Helpers\Schedule\Room;
use Volunteersystem\Helpers\Uuid;
use Volunteersystem\Test\Unit\TestCase;

class EventTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Schedule\Event::__construct
     * @covers \Volunteersystem\Helpers\Schedule\Event::getGuid
     * @covers \Volunteersystem\Helpers\Schedule\Event::getId
     * @covers \Volunteersystem\Helpers\Schedule\Event::getRoom
     * @covers \Volunteersystem\Helpers\Schedule\Event::getTitle
     * @covers \Volunteersystem\Helpers\Schedule\Event::getSubtitle
     * @covers \Volunteersystem\Helpers\Schedule\Event::getType
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDate
     * @covers \Volunteersystem\Helpers\Schedule\Event::getStart
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDuration
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDurationSeconds
     * @covers \Volunteersystem\Helpers\Schedule\Event::getAbstract
     * @covers \Volunteersystem\Helpers\Schedule\Event::getSlug
     * @covers \Volunteersystem\Helpers\Schedule\Event::getTrack
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLogo
     * @covers \Volunteersystem\Helpers\Schedule\Event::getPersons
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLanguage
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDescription
     * @covers \Volunteersystem\Helpers\Schedule\Event::getRecording
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLinks
     * @covers \Volunteersystem\Helpers\Schedule\Event::getAttachments
     * @covers \Volunteersystem\Helpers\Schedule\Event::getUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getFeedbackUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getOriginUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getVideoDownloadUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getEndDate
     */
    public function testCreateDefault(): void
    {
        $room = new Room('Foo');
        $date = new Carbon('2020-12-28T19:30:00+00:00');
        $uuid = Uuid::uuid();
        $event = new Event(
            $uuid,
            1,
            $room,
            'Some stuff',
            'sub stuff',
            'Talk',
            $date,
            '19:30:00',
            '00:50',
            'Doing stuff is hard, plz try again',
            '1-some-stuff',
            new ConferenceTrack('Security'),
        );

        $this->assertEquals($uuid, $event->getGuid());
        $this->assertEquals(1, $event->getId());
        $this->assertEquals($room, $event->getRoom());
        $this->assertEquals('Some stuff', $event->getTitle());
        $this->assertEquals('sub stuff', $event->getSubtitle());
        $this->assertEquals('Talk', $event->getType());
        $this->assertEquals($date, $event->getDate());
        $this->assertEquals('19:30:00', $event->getStart());
        $this->assertEquals('00:50', $event->getDuration());
        $this->assertEquals('Doing stuff is hard, plz try again', $event->getAbstract());
        $this->assertEquals('1-some-stuff', $event->getSlug());
        $this->assertEquals('Security', $event->getTrack()->getName());
        $this->assertNull($event->getLogo());
        $this->assertEquals([], $event->getPersons());
        $this->assertNull($event->getLanguage());
        $this->assertNull($event->getDescription());
        $this->assertNull($event->getRecording());
        $this->assertEquals([], $event->getLinks());
        $this->assertEquals([], $event->getAttachments());
        $this->assertNull($event->getUrl());
        $this->assertNull($event->getVideoDownloadUrl());
        $this->assertNull($event->getFeedbackUrl());
        $this->assertNull($event->getOriginUrl());
        $this->assertEquals('2020-12-28T20:20:00+00:00', $event->getEndDate()->format(Carbon::RFC3339));
    }

    /**
     * @covers \Volunteersystem\Helpers\Schedule\Event::__construct
     * @covers \Volunteersystem\Helpers\Schedule\Event::getGuid
     * @covers \Volunteersystem\Helpers\Schedule\Event::getId
     * @covers \Volunteersystem\Helpers\Schedule\Event::getRoom
     * @covers \Volunteersystem\Helpers\Schedule\Event::getTitle
     * @covers \Volunteersystem\Helpers\Schedule\Event::setTitle
     * @covers \Volunteersystem\Helpers\Schedule\Event::getSubtitle
     * @covers \Volunteersystem\Helpers\Schedule\Event::getType
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDate
     * @covers \Volunteersystem\Helpers\Schedule\Event::getStart
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDuration
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDurationSeconds
     * @covers \Volunteersystem\Helpers\Schedule\Event::getAbstract
     * @covers \Volunteersystem\Helpers\Schedule\Event::getSlug
     * @covers \Volunteersystem\Helpers\Schedule\Event::getTrack
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLogo
     * @covers \Volunteersystem\Helpers\Schedule\Event::getPersons
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLanguage
     * @covers \Volunteersystem\Helpers\Schedule\Event::getDescription
     * @covers \Volunteersystem\Helpers\Schedule\Event::getRecording
     * @covers \Volunteersystem\Helpers\Schedule\Event::getLinks
     * @covers \Volunteersystem\Helpers\Schedule\Event::getAttachments
     * @covers \Volunteersystem\Helpers\Schedule\Event::getUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getFeedbackUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getOriginUrl
     * @covers \Volunteersystem\Helpers\Schedule\Event::getVideoDownloadUrl
     */
    public function testCreate(): void
    {
        $persons = [1337 => 'Some Person'];
        $links = ['https://foo.bar' => 'Foo Bar'];
        $attachments = ['/files/foo.pdf' => 'Suspicious PDF'];
        $event = new Event(
            Uuid::uuid(),
            2,
            new Room('Bar'),
            'Lorem',
            'Ipsum',
            'Workshop',
            new Carbon('2021-01-01T00:00:00+00:00'),
            '00:00:00',
            '00:30',
            'Lorem ipsum dolor sit amet',
            '2-lorem',
            new ConferenceTrack('DevOps'),
            '/foo/bar.png',
            $persons,
            'de',
            'Foo bar is awesome! & That\'s why...',
            new EventRecording('CC BY SA', false),
            $links,
            $attachments,
            'https://foo.bar/2-lorem',
            'https://videos.orem.ipsum/2-lorem.mp4',
            'https://videos.orem.ipsum/2-lorem/feedback',
            'https://some.example/2-lorem/',
        );

        $this->assertEquals('/foo/bar.png', $event->getLogo());
        $this->assertEquals($persons, $event->getPersons());
        $this->assertEquals('de', $event->getLanguage());
        $this->assertEquals('Foo bar is awesome! & That\'s why...', $event->getDescription());
        $this->assertNotNull($event->getRecording());
        $this->assertEquals('CC BY SA', $event->getRecording()->getLicense());
        $this->assertEquals($links, $event->getLinks());
        $this->assertEquals($attachments, $event->getAttachments());
        $this->assertEquals('https://foo.bar/2-lorem', $event->getUrl());
        $this->assertEquals('https://videos.orem.ipsum/2-lorem.mp4', $event->getVideoDownloadUrl());
        $this->assertEquals('https://videos.orem.ipsum/2-lorem/feedback', $event->getFeedbackUrl());
        $this->assertEquals('https://some.example/2-lorem/', $event->getOriginUrl());

        $event->setTitle('Event title');
        $this->assertEquals('Event title', $event->getTitle());
    }
}
