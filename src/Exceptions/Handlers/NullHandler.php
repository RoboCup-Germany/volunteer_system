<?php

declare(strict_types=1);

namespace Volunteersystem\Exceptions\Handlers;

use Volunteersystem\Http\Request;
use Throwable;

class NullHandler extends Legacy
{
    public function render(Request $request, Throwable $e): void
    {
    }
}
