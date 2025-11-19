<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Logger;

use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Logger\UserAwareLogger;
use Volunteersystem\Models\LogEntry;
use Volunteersystem\Models\User\User;
use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LogLevel;

class UserAwareLoggerTest extends TestCase
{
    use HasDatabase;

    /**
     * @covers \Volunteersystem\Logger\UserAwareLogger::createEntry
     * @covers \Volunteersystem\Logger\UserAwareLogger::setAuth
     */
    public function testLog(): void
    {
        $this->initDatabase(); // To be able to run the test by itself

        $user = User::factory(['id' => 1, 'name' => 'admin'])->make();

        /** @var LogEntry|MockObject $logEntry */
        $logEntry = $this->getMockBuilder(LogEntry::class)
            ->addMethods(['create'])
            ->getMock();
        $logEntry->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [['level' => LogLevel::INFO, 'message' => 'Some more informational foo']],
                [['level' => LogLevel::INFO, 'message' => 'Some even more informational bar', 'user_id' => 1]]
            );

        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        $auth->expects($this->exactly(2))
            ->method('user')
            ->willReturnOnConsecutiveCalls(
                null,
                $user
            );

        $logger = new UserAwareLogger($logEntry);
        $logger->setAuth($auth);

        $logger->log(LogLevel::INFO, 'Some more informational foo');
        $logger->log(LogLevel::INFO, 'Some even more informational bar');
    }
}
