<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models;

use Volunteersystem\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /** @var string */
    protected $model = Group::class; // phpcs:ignore

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}
