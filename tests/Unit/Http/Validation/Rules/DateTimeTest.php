<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\DateTime;
use Volunteersystem\Test\Unit\TestCase;

class DateTimeTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\DateTime::__construct
     * @covers \Volunteersystem\Http\Validation\Rules\DateTime::validate
     */
    public function testValidate(): void
    {
        $rule = new DateTime();
        $this->assertTrue($rule->validate('2042-01-01T13:37'));
        $this->assertFalse($rule->validate('2042-01-01 13:37'));

        $rule = new DateTime('Y-m-d H:i:s');
        $this->assertTrue($rule->validate('2042-01-01 13:37:42'));
        $this->assertFalse($rule->validate('2042-01-01 13:37'));
    }
}
