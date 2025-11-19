<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api;

use Volunteersystem\Controllers\Api\ApiController;
use Volunteersystem\Http\Response;

class ApiControllerTest extends ApiBaseControllerTest
{
    /**
     * @covers \Volunteersystem\Controllers\Api\ApiController::__construct
     */
    public function testConstruct(): void
    {
        $controller = new class (new Response('{"some":"json"}')) extends ApiController {
            public function getResponse(): Response
            {
                return $this->response;
            }
        };

        $response = $controller->getResponse();

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertJson($response->getContent());

        $this->assertEquals(['*'], $response->getHeader('access-control-allow-origin'));
        $this->assertJson($response->getContent());
    }
}
