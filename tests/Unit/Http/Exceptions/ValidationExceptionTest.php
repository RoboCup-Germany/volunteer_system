<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Exceptions;

use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Validation\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Http\Exceptions\ValidationException::__construct
     * @covers \Volunteersystem\Http\Exceptions\ValidationException::getValidator
     */
    public function testConstruct(): void
    {
        /** @var Validator|MockObject $validator */
        $validator = $this->createMock(Validator::class);

        $exception = new ValidationException($validator);

        $this->assertEquals($validator, $exception->getValidator());
    }
}
