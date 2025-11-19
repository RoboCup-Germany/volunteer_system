<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Events\Listener;

use Volunteersystem\Config\Config;
use Volunteersystem\Events\Listener\Messages;
use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\Message;
use Volunteersystem\Models\User\Settings;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\Test\TestLogger;

class MessagesTest extends TestCase
{
    use HasDatabase;

    protected TestLogger $log;

    /**
     * @covers \Volunteersystem\Events\Listener\Messages::created
     * @covers \Volunteersystem\Events\Listener\Messages::__construct
     * @covers \Volunteersystem\Events\Listener\Messages::sendMail
     */
    public function testCreated(): void
    {
        /** @var VolunteersystemMailer|MockObject $mailer */
        $mailer = $this->createMock(VolunteersystemMailer::class);
        /** @var User $user */
        $user = User::factory()
            ->has(Settings::factory([
                'email_messages' => true,
            ]))
            ->create();
        $message = Message::factory()->create(['receiver_id' => $user->id]);

        $mailer->expects($this->once())
            ->method('sendViewTranslated')
            ->willReturnCallback(function (
                User $receiver,
                string $subject,
                string $template,
                array $data
            ) use ($user): bool {
                $this->assertEquals($user->id, $receiver->id);
                $this->assertEquals('notification.messages.new', $subject);
                $this->assertEquals('emails/messages-new', $template);
                $this->assertArrayHasKey('username', $data);
                $this->assertArrayHasKey('sender', $data);
                $this->assertArrayHasKey('send_message', $data);
                return true;
            });

        $handler = new Messages($this->log, $mailer);
        $handler->created($message);
    }

    /**
     * @covers \Volunteersystem\Events\Listener\Messages::created
     */
    public function testCreatedNoEmail(): void
    {
        /** @var VolunteersystemMailer|MockObject $mailer */
        $mailer = $this->createMock(VolunteersystemMailer::class);
        /** @var User $user */
        $user = User::factory()
            ->has(Settings::factory([
                'email_messages' => false,
            ]))
            ->create();
        $message = Message::factory()->create(['receiver_id' => $user->id]);
        $mailer->expects($this->never())->method('sendViewTranslated');

        $handler = new Messages($this->log, $mailer);
        $handler->created($message);
    }

    protected function setUp(): void
    {
        $this->log = new TestLogger();

        parent::setUp();
        $this->initDatabase();
        $this->app->instance('config', new Config());
    }
}
