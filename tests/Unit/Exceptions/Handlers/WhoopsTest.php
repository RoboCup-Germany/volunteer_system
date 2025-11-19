<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Exceptions\Handlers;

use Volunteersystem\Application;
use Volunteersystem\Exceptions\Handlers\Whoops;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Request;
use Volunteersystem\Test\Unit\TestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRunner;
use Whoops\RunInterface as WhoopsRunnerInterface;

class WhoopsTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Exceptions\Handlers\Whoops::__construct
     * @covers \Volunteersystem\Exceptions\Handlers\Whoops::render
     * @covers \Volunteersystem\Exceptions\Handlers\Whoops::getPrettyPageHandler
     * @covers \Volunteersystem\Exceptions\Handlers\Whoops::getJsonResponseHandler
     * @covers \Volunteersystem\Exceptions\Handlers\Whoops::getData
     */
    public function testRender(): void
    {
        /** @var Application|MockObject $app */
        $app = $this->createMock(Application::class);
        /** @var Authenticator|MockObject $auth */
        $auth = $this->createMock(Authenticator::class);
        /** @var Request|MockObject $request */
        $request = $this->createMock(Request::class);
        /** @var WhoopsRunnerInterface|MockObject $whoopsRunner */
        $whoopsRunner = $this->getMockForAbstractClass(WhoopsRunnerInterface::class);
        /** @var PrettyPageHandler|MockObject $prettyPageHandler */
        $prettyPageHandler = $this->createMock(PrettyPageHandler::class);
        /** @var JsonResponseHandler|MockObject $jsonResponseHandler */
        $jsonResponseHandler = $this->createMock(JsonResponseHandler::class);
        /** @var Exception|MockObject $exception */
        $exception = $this->createMock(Exception::class);

        $this->setExpects($request, 'isXmlHttpRequest', null, true);

        $this->setExpects($prettyPageHandler, 'setApplicationPaths');
        $this->setExpects($prettyPageHandler, 'addDataTable');

        $this->setExpects($jsonResponseHandler, 'setJsonApi', [true]);
        $this->setExpects($jsonResponseHandler, 'addTraceToOutput', [true]);

        $app->expects($this->exactly(3))
            ->method('make')
            ->withConsecutive(
                [WhoopsRunner::class],
                [PrettyPageHandler::class],
                [JsonResponseHandler::class]
            )
            ->willReturnOnConsecutiveCalls(
                $whoopsRunner,
                $prettyPageHandler,
                $jsonResponseHandler
            );
        $app->expects($this->once())
            ->method('has')
            ->with('authenticator')
            ->willReturn(true);
        $app->expects($this->once())
            ->method('get')
            ->with('authenticator')
            ->willReturn($auth);

        $auth->expects($this->once())
            ->method('user')
            ->willReturn(null);

        $whoopsRunner
            ->expects($this->exactly(2))
            ->method('pushHandler')
            ->withConsecutive(
                [$prettyPageHandler],
                [$jsonResponseHandler]
            );
        $this->setExpects($whoopsRunner, 'writeToOutput', [false]);
        $this->setExpects($whoopsRunner, 'allowQuit', [false]);
        $this->setExpects($whoopsRunner, 'handleException', [$exception]);

        $handler = new Whoops($app);
        $handler->render($request, $exception);
    }
}
