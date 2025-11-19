<?php

declare(strict_types=1);

namespace Volunteersystem\Helpers;

use Carbon\Carbon;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\ShiftCalendarRenderer;
use Volunteersystem\ShiftsFilter;
use Illuminate\Support\Collection;

class ShiftsRenderer
{
    /**
     * This is glue code to force the legacy shift renderer to the new code
     *
     * @param Collection|Shift[] $shifts
     */
    public function render(Collection | array $shifts): string
    {
        /** @var array[] $neededVolunteerTypes */
        $neededVolunteerTypes = [];
        /** @var ShiftEntry[][] $shiftEntries */
        $shiftEntries = [];

        foreach ($shifts as $shift) {
            $shiftEntries[$shift->id] = $shift->shiftEntries;

            if (!$shift->schedule) {
                $volunteerTypes = $shift->neededVolunteerTypes;
            } else {
                if ($shift->schedule->needed_from_shift_type) {
                    $volunteerTypes = $shift->shiftType->neededVolunteerTypes;
                } else {
                    $volunteerTypes = $shift->location->neededVolunteerTypes;
                }
            }

            $neededVolunteerTypes[$shift->id] = [];
            foreach ($volunteerTypes as $nVolunteerType) {
                $data = $nVolunteerType->toArray();
                $data['id'] = $nVolunteerType->volunteerType->id;
                $data['name'] = $nVolunteerType->volunteerType->name;
                $data['restricted'] = $nVolunteerType->volunteerType->restricted;
                $data['shift_self_signup'] = $nVolunteerType->volunteerType->shift_self_signup;
                $neededVolunteerTypes[$shift->id][] = $data;
            }
        }

        return $this->renderShiftCalendar($shifts, $neededVolunteerTypes, $shiftEntries);
    }

    /**
     * @param Collection|Shift[] $shifts
     * @param array[] $neededVolunteerTypes
     * @param ShiftEntry[][] $shiftEntries
     * @codeCoverageIgnore
     */
    protected function renderShiftCalendar(
        array | Collection $shifts,
        array $neededVolunteerTypes,
        array $shiftEntries
    ): string {
        if (!$shifts instanceof Collection) {
            $shifts = collect($shifts);
        }

        /** @var Carbon $start */
        $start = $shifts->min('start');
        /** @var Carbon $end */
        $end = $shifts->max('end');

        $shiftsFilter = new ShiftsFilter();
        $shiftsFilter->setStartTime($start ? $start->timestamp : 0);
        $shiftsFilter->setEndTime($end ? $end->timestamp : 0);

        $renderer = new ShiftCalendarRenderer($shifts, $neededVolunteerTypes, $shiftEntries, $shiftsFilter);

        return $renderer->render();
    }
}
