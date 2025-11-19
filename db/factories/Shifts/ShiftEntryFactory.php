<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftEntryFactory extends Factory
{
    /** @var string */
    protected $model = ShiftEntry::class; // phpcs:ignore

    public function definition(): array
    {
        $freeloaded_by = $this->faker->optional(.01)->passthrough(User::factory());

        return [
            'shift_id'           => Shift::factory(),
            'volunteer_type_id'      => VolunteerType::factory(),
            'user_id'            => User::factory(),
            'user_comment'       => $this->faker->optional(.05, '')->text(),
            'freeloaded_by'      => $freeloaded_by,
            'freeloaded_comment' => $freeloaded_by ? $this->faker->text() : '',
        ];
    }
}
