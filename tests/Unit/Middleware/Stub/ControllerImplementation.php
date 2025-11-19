<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Middleware\Stub;

use Volunteersystem\Controllers\BaseController;

class ControllerImplementation extends BaseController
{
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function actionStub(): string
    {
        return '';
    }
}
