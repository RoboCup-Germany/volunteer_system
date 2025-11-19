<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\After;
use Volunteersystem\Test\Unit\TestCase;

class AfterTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\After::compare
     */
    public function testCompare(): void
    {
        $date = '2024-01-02 13:37';
        $rule = new After($date);

        // After
        $this->assertTrue($rule->validate('2042-01-01'));
        $this->assertFalse($rule->validate('2024-01-02 13:36'));

        // Equals
        $this->assertFalse($rule->validate($date));
        $rule = new After($date, true);
        $this->assertTrue($rule->validate($date));
    }
}
