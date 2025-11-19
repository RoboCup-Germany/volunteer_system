<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Feature\Controllers\Metrics;

use Volunteersystem\Controllers\Metrics\Controller;
use Volunteersystem\Test\Feature\ApplicationFeatureTest;

class ControllerTest extends ApplicationFeatureTest
{
    /**
     * @covers \Volunteersystem\Controllers\Metrics\Controller::metrics
     */
    public function testMetrics(): void
    {
        config([
            'api_key' => null,
            'metrics' => ['work' => [60 * 60], 'voucher' => [1]],
            'themes' => [1 => ['name' => 'Test']],
        ]);

        /** @var Controller $controller */
        $controller = app()->make(Controller::class);
        $response = $controller->metrics();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
