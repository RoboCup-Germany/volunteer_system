<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models\User;

use Volunteersystem\Models\User\PasswordReset;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PasswordResetFactory extends Factory
{
    /** @var string */
    protected $model = PasswordReset::class; // phpcs:ignore

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => bin2hex(random_bytes(16)),
        ];
    }
}
