<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http;

use Volunteersystem\Http\Response;
use Volunteersystem\Http\ResponseServiceProvider;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ResponseServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Http\ResponseServiceProvider::register()
     */
    public function testRegister(): void
    {
        /** @var Response|MockObject $response */
        $response = $this->getMockBuilder(Response::class)
            ->getMock();

        $app = $this->getApp();

        $this->setExpects($app, 'make', [Response::class], $response);
        $app->expects($this->exactly(3))
            ->method('instance')
            ->withConsecutive(
                [Response::class, $response],
                [SymfonyResponse::class, $response],
                ['response', $response]
            );

        $serviceProvider = new ResponseServiceProvider($app);
        $serviceProvider->register();
    }
}
