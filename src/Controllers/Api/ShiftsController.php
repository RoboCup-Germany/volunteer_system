<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\VolunteerTypeResource;
use Volunteersystem\Controllers\Api\Resources\LocationResource;
use Volunteersystem\Controllers\Api\Resources\ShiftWithEntriesResource;
use Volunteersystem\Controllers\Api\Resources\UserResource;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftType;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;

class ShiftsController extends ApiController
{
    use UsesAuth;

    public function entriesByVolunteertype(Request $request): Response
    {
        $id = (int) $request->getAttribute('volunteertype_id');
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::findOrFail($id);

        // From users assigned to shifts
        $shiftsByEntries = Shift::query()
            ->join('shift_entries', 'shift_entries.shift_id', 'shifts.id')
            ->where('volunteer_type_id', $volunteerType->id)
            ->select('shifts.*');

        // Needed by a shift directly
        $shiftsByShift = Shift::query()
            ->join('needed_volunteer_types', 'needed_volunteer_types.shift_id', 'shifts.id')
            ->where('needed_volunteer_types.volunteer_type_id', $volunteerType->id)
            ->select('shifts.*');

        // Needed by connected schedule via shift location
        $shiftsByScheduleLocation = Shift::query()
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', false)
            ->join('needed_volunteer_types', 'needed_volunteer_types.location_id', 'shifts.location_id')
            ->where('needed_volunteer_types.volunteer_type_id', $volunteerType->id)
            ->select('shifts.*');

        // Needed by connected schedule via schedule shift type
        $shiftsByScheduleShiftType = Shift::query()
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', true)
            ->join('needed_volunteer_types', 'needed_volunteer_types.shift_type_id', 'schedules.shift_type')
            ->where('needed_volunteer_types.volunteer_type_id', $volunteerType->id)
            ->select('shifts.*');

        $shifts = $shiftsByShift
            ->union($shiftsByEntries)
            ->union($shiftsByScheduleLocation)
            ->union($shiftsByScheduleShiftType);

        return $this->shiftEntriesResponse($shifts);
    }

    public function entriesByLocation(Request $request): Response
    {
        $locationId = (int) $request->getAttribute('location_id');
        /** @var Location $location */
        $location = Location::findOrFail($locationId);

        // Needed by a shift directly
        $shiftsByShift = $location->shifts();

        // Needed by connected schedule via shift location
        $shiftByScheduleLocation = Shift::query()
            ->where('shifts.location_id', $location->id)
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', false)
            ->join('needed_volunteer_types', 'needed_volunteer_types.location_id', 'shifts.location_id')
            ->select('shifts.*');

        // Needed by connected schedule via schedule shift type
        $shiftsByScheduleShiftType = Shift::query()
            ->where('shifts.location_id', $location->id)
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', true)
            ->join('needed_volunteer_types', 'needed_volunteer_types.shift_type_id', 'schedules.shift_type')
            ->select('shifts.*');

        $shifts = $shiftsByShift
            ->union($shiftByScheduleLocation)
            ->union($shiftsByScheduleShiftType);

        return $this->shiftEntriesResponse($shifts);
    }

    public function entriesByShiftType(Request $request): Response
    {
        $shiftTypeId = (int) $request->getAttribute('shifttype_id');
        /** @var ShiftType $shiftType */
        $shiftType = ShiftType::findOrFail($shiftTypeId);

        // Needed by a shift directly
        $shiftsByShift = $shiftType->shifts();

        // Needed by connected schedule via shift location
        $shiftsByScheduleLocation = Shift::query()
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', false)
            ->where('schedules.shift_type', $shiftType->id)
            ->join('needed_volunteer_types', 'needed_volunteer_types.location_id', 'shifts.location_id')
            ->select('shifts.*');

        // Needed by connected schedule via schedule shift type
        $shiftsByScheduleShiftType = Shift::query()
            ->join('schedule_shift', 'schedule_shift.shift_id', 'shifts.id')
            ->join('schedules', 'schedules.id', 'schedule_shift.schedule_id')
            ->where('schedules.needed_from_shift_type', true)
            ->where('schedules.shift_type', $shiftType->id)
            ->select('shifts.*');

        $shifts = $shiftsByShift
            ->union($shiftsByScheduleLocation)
            ->union($shiftsByScheduleShiftType);

        return $this->shiftEntriesResponse($shifts);
    }

    public function entriesByUser(Request $request): Response
    {
        $id = $request->getAttribute('user_id');
        $user = $this->getUser($id);

        $shifts = Shift::query()
            ->join('shift_entries', 'shift_entries.shift_id', 'shifts.id')
            ->where('shift_entries.user_id', $user->id)
            ->groupBy('shifts.id')
            ->select('shifts.*');

        return $this->shiftEntriesResponse($shifts);
    }

    protected function shiftEntriesResponse(BuilderContract $shifts): Response
    {
        $shifts = $shifts
            ->with([
                'neededVolunteerTypes.volunteerType',
                'location.neededVolunteerTypes.volunteerType',
                'shiftEntries.volunteerType',
                'shiftEntries.user.contact',
                'shiftEntries.user.personalData',
                'shiftType',
                'scheduleShift',
                'schedule.shiftType.neededVolunteerTypes.volunteerType',
            ])
            ->orderBy('start')
            ->get();
        /** @var Shift[]|Collection $shifts */

        $shiftEntries = [];
        // Blob of not-optimized mediocre pseudo-serialization
        foreach ($shifts as $shift) {
            // Get all needed/used volunteer types
            /** @var Collection|NeededVolunteerType[] $neededVolunteerTypes */
            $neededVolunteerTypes = $this->getNeededVolunteerTypes($shift);

            if ($neededVolunteerTypes->isEmpty()) {
                continue;
            }

            $volunteerTypes = new Collection();
            foreach ($neededVolunteerTypes as $neededVolunteerType) {
                $entries = $neededVolunteerType->entries ?: new Collection();

                // Skip empty entries
                if ($neededVolunteerType->count <= 0 && $entries->isEmpty()) {
                    continue;
                }

                $entries = $entries->map(fn(ShiftEntry $entry) => [
                    'user' => UserResource::toIdentifierArray($entry->user),
                    'freeloaded_by' => $entry->freeloaded_by
                        ? UserResource::toIdentifierArray($entry->freeloadedBy)
                        : null,
                ]);
                $volunteerTypeData = VolunteerTypeResource::toIdentifierArray($neededVolunteerType->volunteerType);
                $volunteerTypes[] = new Collection([
                    'volunteer_type' => $volunteerTypeData,
                    'needs' => $neededVolunteerType->count,
                    'entries' => $entries,
                ]);
            }

            $locationData = new LocationResource($shift->location);
            $shiftEntries[] = (new ShiftWithEntriesResource($shift))->toArray($locationData, $volunteerTypes);
        }

        $data = ['data' => $shiftEntries];
        return $this->response
            ->withContent(json_encode($data));
    }

    /**
     * Collect all needed volunteer types
     */
    protected function getNeededVolunteerTypes(Shift $shift): Collection
    {
        $neededVolunteerTypes = new Collection();
        if (!$shift->schedule) {
            // Get from shift
            $neededVolunteerTypes = $shift->neededVolunteerTypes;
        } elseif ($shift->schedule->needed_from_shift_type) {
            // Load instead from shift type
            $neededVolunteerTypes = $shift->schedule->shiftType->neededVolunteerTypes;
        } elseif (!$shift->schedule->needed_from_shift_type) {
            // Load instead from location
            $neededVolunteerTypes = $shift->location->neededVolunteerTypes;
        }

        // Create new instances of needed volunteer types to allow extension with entries per shift
        $neededVolunteerTypes = $neededVolunteerTypes->map(function ($value) {
            return clone $value;
        });

        // Add entries and additional volunteer types from manually added users
        foreach ($shift->shiftEntries as $entry) {
            // Ensure that volunteer type exists in list; add it if not
            $neededVolunteerType = $neededVolunteerTypes->where('volunteer_type_id', $entry->volunteerType->id)->first();
            if (!$neededVolunteerType) {
                $neededVolunteerType = new NeededVolunteerType([
                    'shift_id' => $shift->id,
                    'volunteer_type_id' => $entry->volunteerType->id,
                    'count' => 0,
                ]);
                $neededVolunteerTypes[] = $neededVolunteerType;
            }

            // Initialize entries attribute for manually added users
            if (!isset($neededVolunteerType->entries)) {
                $neededVolunteerType->entries = new Collection();
            }

            // Add entries to needed volunteer type
            $neededVolunteerType->entries[] = $entry;
        }

        return $neededVolunteerTypes;
    }
}
