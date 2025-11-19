<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserVolunteerTypeFactory extends Factory
{
    /** @var string */
    protected $model = UserVolunteerType::class; // phpcs:ignore

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'volunteer_type_id'   => VolunteerType::factory(),
            'confirm_user_id' => $this->faker->optional()->passthrough(User::factory()),
            'supporter'       => $this->faker->boolean(),
        ];
    }
}
