<?php

declare(strict_types=1);

namespace Volunteersystem\Http\Validation;

use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Request;

trait ValidatesRequest
{
    protected ?Validator $validator;

    protected function validate(Request $request, array $rules): array
    {
        $isValid = $this->validator->validate(
            (array) $request->getParsedBody(),
            $rules
        );

        if (!$isValid) {
            throw new ValidationException($this->validator);
        }

        return $this->validator->getData();
    }

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }
}
