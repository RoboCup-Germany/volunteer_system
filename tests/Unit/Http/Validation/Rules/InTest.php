<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\In;
use Volunteersystem\Test\Unit\TestCase;

class InTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\In::__construct
     */
    public function testConstruct(): void
    {
        $rule = new In('foo,bar');

        $this->assertTrue($rule->validate('foo'));
        $this->assertTrue($rule->validate('bar'));

        $this->assertFalse($rule->validate('baz'));
        $this->assertFalse($rule->validate('foo,bar'));
    }
}
