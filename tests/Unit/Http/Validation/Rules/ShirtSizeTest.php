<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Rules;

use Volunteersystem\Http\Validation\Rules\ShirtSize;
use Volunteersystem\Test\Unit\ServiceProviderTest;

class ShirtSizeTest extends ServiceProviderTest
{
    private ShirtSize $subject;

    public function setUp(): void
    {
        $app = $this->createAndSetUpAppWithConfig([]);
        $app->get('config')->set('tshirt_sizes', [
            'S' => 'Small Straight-Cut',
            'M' => 'Medium Straight-Cut',
        ]);
        $this->subject = new ShirtSize();
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function provideTestValidateData(): array
    {
        return [
            'empty string' => ['', false],
            'null' => [null, false],
            '0' => [0, false],
            '"S" (known value)' => ['S', true],
            '"M" (known value)' => ['M', true],
            '"L" (unknown value)' => ['L', false],
        ];
    }

    /**
     * @covers \Volunteersystem\Http\Validation\Rules\ShirtSize::__construct
     * @dataProvider provideTestValidateData
     */
    public function testValidate(mixed $value, bool $expectedValid): void
    {
        self::assertSame($expectedValid, $this->subject->validate($value));
    }
}
