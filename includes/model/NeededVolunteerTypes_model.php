<?php

use Volunteersystem\Database\Db;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Illuminate\Database\Eloquent\Collection;

/**
 * Returns all needed volunteertypes and already taken needs.
 *
 * @param Shift $shift
 * @return array
 */
function NeededVolunteerTypes_by_shift($shift)
{
    $needed_volunteertypes_source = [];
    // Select from shift
    if (!$shift->schedule) {
        $needed_volunteertypes_source = Db::select(
            '
        SELECT
            `needed_volunteer_types`.*,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `needed_volunteer_types`
        JOIN `volunteer_types` ON `volunteer_types`.`id` = `needed_volunteer_types`.`volunteer_type_id`
        WHERE `needed_volunteer_types`.`shift_id` = ?
        ORDER BY `location_id` DESC
        ',
            [$shift->id]
        );
    }

    // Get needed by shift type
    if ($shift->schedule && $shift->schedule->needed_from_shift_type) {
        $needed_volunteertypes_source = Db::select('
        SELECT
            `needed_volunteer_types`.*,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `needed_volunteer_types`
        JOIN `volunteer_types` ON `volunteer_types`.`id` = `needed_volunteer_types`.`volunteer_type_id`
        WHERE `needed_volunteer_types`.`shift_type_id` = ?
        ORDER BY `location_id` DESC
        ', [$shift->shift_type_id]);
    }

    // Load from room
    if ($shift->schedule && !$shift->schedule->needed_from_shift_type) {
        $needed_volunteertypes_source = Db::select('
        SELECT
            `needed_volunteer_types`.*,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `needed_volunteer_types`
        JOIN `volunteer_types` ON `volunteer_types`.`id` = `needed_volunteer_types`.`volunteer_type_id`
        WHERE `needed_volunteer_types`.`location_id` = ?
        ORDER BY `location_id` DESC
        ', [$shift->location_id]);
    }

    /** @var ShiftEntry[]|Collection $shift_entries */
    $shift_entries = ShiftEntry::with('user', 'volunteerType')
        ->where('shift_id', $shift->id)
        ->get();
    $needed_volunteertypes = [];
    foreach ($needed_volunteertypes_source as $volunteertype) {
        $volunteertype['shift_entries'] = [];
        $volunteertype['taken'] = 0;
        foreach ($shift_entries as $shift_entry) {
            if ($shift_entry->volunteer_type_id == $volunteertype['volunteer_type_id'] && !$shift_entry->freeloaded_by) {
                $volunteertype['taken']++;
                $volunteertype['shift_entries'][] = $shift_entry;
            }
        }

        $needed_volunteertypes[] = $volunteertype;
    }

    return $needed_volunteertypes;
}
