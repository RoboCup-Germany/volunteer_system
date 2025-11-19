<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Stub;

use Volunteersystem\Controllers\BaseController;

class ControllerImplementation extends BaseController
{
    /** @var string[]|string[][] */
    protected array $permissions = [
        'foo',
        'lorem' => [
            'ipsum',
            'dolor',
        ],
    ];
}
