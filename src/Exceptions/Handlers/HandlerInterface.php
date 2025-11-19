<?php

declare(strict_types=1);

namespace Volunteersystem\Exceptions\Handlers;

use Volunteersystem\Http\Request;
use Throwable;

interface HandlerInterface
{
    public function render(Request $request, Throwable $e): void;

    public function report(Throwable $e): void;
}
