<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models\Shifts;

use Volunteersystem\Models\Shifts\Schedule;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleShiftFactory extends Factory
{
    /** @var string */
    protected $model = ScheduleShift::class; // phpcs:ignore

    public function definition(): array
    {
        return [
            'shift_id' =>    Shift::factory(),
            'schedule_id' => Schedule::factory(),
            'guid' =>        $this->faker->uuid(),
        ];
    }
}
