<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Api\Stub;

use Volunteersystem\Controllers\Api\ApiController;
use Volunteersystem\Controllers\Api\UsesAuth;
use Volunteersystem\Models\User\User;

class UsesAuthImplementation extends ApiController
{
    use UsesAuth;

    public function user(string|int $id): ?User
    {
        return $this->getUser($id);
    }
}
