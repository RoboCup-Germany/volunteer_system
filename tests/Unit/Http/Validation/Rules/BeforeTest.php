<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\Before;
use Volunteersystem\Test\Unit\TestCase;

class BeforeTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\Before::compare
     */
    public function testCompare(): void
    {
        $date = '2024-01-02 13:37';
        $rule = new Before($date);

        // Before
        $this->assertTrue($rule->validate('2001-01-01'));
        $this->assertFalse($rule->validate('2024-01-02 13:38'));

        // Equals
        $this->assertFalse($rule->validate($date));
        $rule = new Before($date, true);
        $this->assertTrue($rule->validate($date));
    }
}
