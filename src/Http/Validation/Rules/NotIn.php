<?php

declare(strict_types=1);

namespace Volunteersystem\Http\Validation\Rules;

class NotIn extends In
{
    public function validate(mixed $input): bool
    {
        return !parent::validate($input);
    }
}
