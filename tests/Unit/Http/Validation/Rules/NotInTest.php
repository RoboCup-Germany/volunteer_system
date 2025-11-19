<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\NotIn;
use Volunteersystem\Test\Unit\TestCase;

class NotInTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Validation\Rules\NotIn::validate
     */
    public function testConstruct(): void
    {
        $rule = new NotIn('foo,bar');

        $this->assertTrue($rule->validate('lorem'));
        $this->assertFalse($rule->validate('foo'));
    }
}
