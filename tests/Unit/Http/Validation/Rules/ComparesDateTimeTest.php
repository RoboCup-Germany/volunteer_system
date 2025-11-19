<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Test\Unit\Http\Validation\Rules\Stub\UsesComparesDateTime;
use Volunteersystem\Test\Unit\TestCase;

class ComparesDateTimeTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::__construct
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::validate
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::toDateTime
     */
    public function testValidate(): void
    {
        $rule = new UsesComparesDateTime('2024-01-02 13:37');
        $rule->setCallback(function ($input, $comparison) {
            /** @var Carbon $input */
            $this->assertInstanceOf(Carbon::class, $input);
            $this->assertEquals('2042-10-11 00:00:00', $input->toDateTimeString());

            $this->assertInstanceOf(Carbon::class, $comparison);
            $this->assertEquals('2024-01-02 13:37:00', $comparison->toDateTimeString());

            return true;
        });

        $this->assertTrue($rule->validate('2042-10-11'));
    }

    /**
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::__construct
     */
    public function testCompareTo(): void
    {
        $rule = new UsesComparesDateTime('2024-01-02 13:37');

        /** @var Carbon $comparisonTarget */
        $comparisonTarget = $rule->getCompareTo();
        $this->assertInstanceOf(Carbon::class, $comparisonTarget);
        $this->assertEquals('2024-01-02 13:37:00', $comparisonTarget->toDateTimeString());
    }

    /**
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::__construct
     */
    public function testOrEqual(): void
    {
        $rule = new UsesComparesDateTime('2024-01-02 13:37');
        $this->assertFalse($rule->getOrEqual());

        $rule = new UsesComparesDateTime('2024-01-02 13:37', true);
        $this->assertTrue($rule->getOrEqual());
    }

    /**
     * @covers \Volunteersystem\Http\Validation\Rules\ComparesDateTime::toDateTime
     */
    public function testValidateDateTimeStaysSame(): void
    {
        $a = Carbon::now();
        $b = Carbon::now();

        $rule = new UsesComparesDateTime($a);
        $rule->setCallback(function ($input, $comparison) use ($a, $b) {
            $this->assertEquals($b, $input);
            $this->assertEquals($a, $comparison);

            return false;
        });

        $this->assertEquals($a, $rule->getCompareTo());
        $this->assertFalse($rule->validate($b));
    }
}
