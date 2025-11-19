<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Metrics;

use Volunteersystem\Controllers\Metrics\MetricsEngine;
use Volunteersystem\Test\Unit\TestCase;

class MetricsEngineTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::escape
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::formatData
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::formatValue
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::get
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::renderLabels
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::renderValue
     */
    public function testGet(): void
    {
        $engine = new MetricsEngine();

        $this->assertEquals("\n", $engine->get('/metrics'));

        $this->assertEquals("volunteersystem_users 13\n", $engine->get('/metrics', ['users' => 13]));

        $this->assertEquals("volunteersystem_bool_val 0\n", $engine->get('/metrics', ['bool_val' => false]));

        $this->assertEquals('# Lorem \n Ipsum' . "\n", $engine->get('/metrics', ["Lorem \n Ipsum"]));

        $this->assertEquals(
            'volunteersystem_foo{lorem="ip\\\\sum"} \\"lorem\\n\\\\ipsum\\"' . "\n",
            $engine->get('/metrics', [
                'foo' => ['labels' => ['lorem' => 'ip\\sum'], 'value' => "\"lorem\n\\ipsum\""],
            ])
        );

        $this->assertEquals(
            'volunteersystem_foo_count{bar="14"} 42' . "\n",
            $engine->get('/metrics', ['foo_count' => ['labels' => ['bar' => 14], 'value' => 42]])
        );

        $this->assertEquals(
            'volunteersystem_lorem{test="123"} NaN' . "\n" . 'volunteersystem_lorem{test="456"} 999.99' . "\n",
            $engine->get('/metrics', [
                'lorem' => [
                    ['labels' => ['test' => 123], 'value' => 'NaN'],
                    ['labels' => ['test' => 456], 'value' => 999.99],
                ],
            ])
        );

        $this->assertEquals(
            "# HELP volunteersystem_test Some help\\n  text\n# TYPE volunteersystem_test counter\nvolunteersystem_test 99\n",
            $engine->get('/metrics', ['test' => ['help' => "Some help\n  text", 'type' => 'counter', 'value' => 99]])
        );
    }

    /**
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::expandData
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::formatHistogram
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::get
     */
    public function testGetHistogram(): void
    {
        $engine = new MetricsEngine();

        $this->assertEquals(
            <<<'EOD'
# TYPE volunteersystem_test_minimum_histogram histogram
volunteersystem_test_minimum_histogram_bucket{le="3"} 4
volunteersystem_test_minimum_histogram_bucket{le="+Inf"} 4
volunteersystem_test_minimum_histogram_sum 1.337
volunteersystem_test_minimum_histogram_count 4

EOD,
            $engine->get('/metrics', [
                'test_minimum_histogram' => [
                    'type'  => 'histogram', [3 => 4, 'sum' => 1.337],
                ],
            ])
        );

        $this->assertEquals(
            <<<'EOD'
# TYPE volunteersystem_test_short_histogram histogram
volunteersystem_test_short_histogram_bucket{le="0"} 0
volunteersystem_test_short_histogram_bucket{le="60"} 10
volunteersystem_test_short_histogram_bucket{le="120"} 19
volunteersystem_test_short_histogram_bucket{le="+Inf"} 300
volunteersystem_test_short_histogram_sum 123.456
volunteersystem_test_short_histogram_count 300

EOD,
            $engine->get('/metrics', [
                'test_short_histogram' => [
                    'type'  => 'histogram',
                    'value' => [120 => 19, '+Inf' => 300, 60 => 10, 0 => 0, 'sum' => 123.456],
                ],
            ])
        );

        $this->assertEquals(
            <<<'EOD'
# TYPE volunteersystem_test_multiple_histogram histogram
volunteersystem_test_multiple_histogram_bucket{handler="foo",le="0.1"} 32
volunteersystem_test_multiple_histogram_bucket{handler="foo",le="3"} 99
volunteersystem_test_multiple_histogram_bucket{handler="foo",le="+Inf"} 99
volunteersystem_test_multiple_histogram_sum{handler="foo"} 42
volunteersystem_test_multiple_histogram_count{handler="foo"} 99
volunteersystem_test_multiple_histogram_bucket{handler="bar",le="0.2"} 0
volunteersystem_test_multiple_histogram_bucket{handler="bar",le="+Inf"} 3
volunteersystem_test_multiple_histogram_sum{handler="bar"} 3
volunteersystem_test_multiple_histogram_count{handler="bar"} 3

EOD,
            $engine->get('/metrics', [
                'test_multiple_histogram' => [
                    'type' => 'histogram',
                    ['labels' => ['handler' => 'foo'], 'sum' => '42', 'value' => ['0.1' => 32, 3 => 99]],
                    ['labels' => ['handler' => 'bar'], 'sum' => '3', 'value' => ['0.2' => 0, '+Inf' => 3]],
                ],
            ])
        );

        $this->assertEquals(
            <<<'EOD'
# TYPE volunteersystem_test_minimum_histogram histogram
volunteersystem_test_minimum_histogram_bucket{le="+Inf"} NaN
volunteersystem_test_minimum_histogram_sum NaN
volunteersystem_test_minimum_histogram_count NaN

EOD,
            $engine->get('/metrics', [
                'test_minimum_histogram' => [
                    'type'  => 'histogram', [],
                ],
            ])
        );
    }

    /**
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::canRender
     */
    public function testCanRender(): void
    {
        $engine = new MetricsEngine();

        $this->assertFalse($engine->canRender('/'));
        $this->assertFalse($engine->canRender('/metrics.foo'));
        $this->assertTrue($engine->canRender('/metrics'));
    }

    /**
     * @covers \Volunteersystem\Controllers\Metrics\MetricsEngine::share
     */
    public function testShare(): void
    {
        $engine = new MetricsEngine();

        $engine->share('foo', 42);
        // Just ignore it
        $this->assertEquals("\n", $engine->get('/metrics'));
    }
}
