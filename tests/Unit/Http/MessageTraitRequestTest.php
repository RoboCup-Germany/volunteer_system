<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Volunteersystem\Test\Unit\Http\Stub\MessageTraitRequestImplementation;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;

class MessageTraitRequestTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @covers \Volunteersystem\Http\MessageTrait::withProtocolVersion
     */
    public function testWithProtocolVersion(): void
    {
        $message = new MessageTraitRequestImplementation();
        $newMessage = $message->withProtocolVersion('0.1');
        $this->assertNotEquals($message, $newMessage);
        $this->assertEquals('0.1', $newMessage->getProtocolVersion());
    }

    /**
     * @covers \Volunteersystem\Http\MessageTrait::getHeaders
     */
    public function testGetHeaders(): void
    {
        $message = new MessageTraitRequestImplementation();
        $newMessage = $message->withHeader('lorem', 'ipsum');

        $this->assertNotEquals($message, $newMessage);
        $this->assertArraySubset(['lorem' => ['ipsum']], $newMessage->getHeaders());
    }

    /**
     * @covers \Volunteersystem\Http\MessageTrait::withBody
     */
    public function testWithBody(): void
    {
        $stream = Stream::create('Test content');
        $message = new MessageTraitRequestImplementation();
        $newMessage = $message->withBody($stream);

        $this->assertNotEquals($message, $newMessage);
        $this->assertEquals('Test content', $newMessage->getContent());
    }
}
