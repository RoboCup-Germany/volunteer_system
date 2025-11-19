<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class NeededVolunteerTypeFactory extends Factory
{
    /** @var string */
    protected $model = NeededVolunteerType::class; // phpcs:ignore

    public function definition(): array
    {
        $type = $this->faker->numberBetween(0, 2);

        return [
            'location_id'   => $type == 0 ? Location::factory() : null,
            'shift_id'      => $type == 1 ? null : Shift::factory(),
            'shift_type_id' => $type == 2 ? null : Shift::factory(),
            'volunteer_type_id' => VolunteerType::factory(),
            'count'         => $this->faker->numberBetween(1, 5),
        ];
    }
}
