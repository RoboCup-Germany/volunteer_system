<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation\Stub;

use Volunteersystem\Controllers\BaseController;
use Volunteersystem\Http\Request;

class ValidatesRequestImplementation extends BaseController
{
    public function validateData(Request $request, array $rules): array
    {
        return $this->validate($request, $rules);
    }

    public function hasValidator(): bool
    {
        return !is_null($this->validator);
    }
}
