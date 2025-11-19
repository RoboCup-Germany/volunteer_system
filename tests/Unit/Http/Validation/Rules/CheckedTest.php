<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\Checked;
use Volunteersystem\Test\Unit\TestCase;

class CheckedTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\Checked::isValid
     * @see TruthyTest
     */
    public function testIsValid(): void
    {
        $rule = new Checked();

        $this->assertTrue($rule->isValid('on'));
        $this->assertFalse($rule->isValid(null));
    }
}
