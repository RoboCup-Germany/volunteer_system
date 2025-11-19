<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Models\User\User;

trait UsesAuth
{
    protected ?Authenticator $auth = null;

    public function setAuth(Authenticator $auth): void
    {
        $this->auth = $auth;
    }

    protected function getUser(int|string $userId): ?User
    {
        if ($userId == 'self' && $this->auth) {
            return $this->auth->user();
        }

        return User::findOrFail($userId);
    }
}
