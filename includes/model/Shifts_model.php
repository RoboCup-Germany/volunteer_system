<?php

use Volunteersystem\Database\Db;
use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftSignupStatus;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\ShiftsFilter;
use Volunteersystem\ShiftSignupState;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @param VolunteerType $volunteertype
 * @return array
 */
function Shifts_by_volunteertype(VolunteerType $volunteertype)
{
    return Db::select('
        SELECT DISTINCT `shifts`.* FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_id` = `shifts`.`id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        WHERE `needed_volunteer_types`.`volunteer_type_id` = ?
        AND s.shift_id IS NULL

        UNION

        /* By shift type */
        SELECT DISTINCT `shifts`.* FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_type_id` = `shifts`.`shift_type_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `needed_volunteer_types`.`volunteer_type_id` = ?
        AND NOT s.shift_id IS NULL
        AND se.needed_from_shift_type = TRUE

        UNION

        /* By location */
        SELECT DISTINCT `shifts`.* FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`location_id` = `shifts`.`location_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `needed_volunteer_types`.`volunteer_type_id` = ?
        AND NOT s.shift_id IS NULL
        AND se.needed_from_shift_type = FALSE
        ', [$volunteertype->id, $volunteertype->id, $volunteertype->id]);
}

/**
 * Returns every shift with needed volunteers in the given time range.
 *
 * @param int               $start timestamp
 * @param int               $end timestamp
 * @param ShiftsFilter|null $filter
 *
 * @return Collection|Shift[]
 */
function Shifts_free($start, $end, ?ShiftsFilter $filter = null)
{
    $start = Carbon::createFromTimestamp($start, Carbon::now()->timezone);
    $end = Carbon::createFromTimestamp($end, Carbon::now()->timezone);

    $shifts = Db::select('
        SELECT *
        FROM (
            SELECT shifts.id, start
            FROM `shifts`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            WHERE (`end` > ? AND `start` < ?)
            AND (SELECT SUM(`count`) FROM `needed_volunteer_types` WHERE `needed_volunteer_types`.`shift_id`=`shifts`.`id`' . ($filter ? ' AND needed_volunteer_types.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            > (SELECT COUNT(*) FROM `shift_entries` WHERE `shift_entries`.`shift_id`=`shifts`.`id` AND shift_entries.`freeloaded_by` IS NULL' . ($filter ? ' AND shift_entries.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            AND s.shift_id IS NULL
            ' . ($filter ? 'AND shifts.location_id IN (' . implode(',', $filter->getLocations()) . ')' : '') . '

            UNION

            /* By shift type */
            SELECT shifts.id, start
            FROM `shifts`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            LEFT JOIN schedules AS se on s.schedule_id = se.id
            WHERE (`end` > ? AND `start` < ?)
            AND (SELECT SUM(`count`) FROM `needed_volunteer_types` WHERE `needed_volunteer_types`.`shift_type_id`=`shifts`.`shift_type_id`' . ($filter ? ' AND needed_volunteer_types.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            > (SELECT COUNT(*) FROM `shift_entries` WHERE `shift_entries`.`shift_id`=`shifts`.`id` AND shift_entries.`freeloaded_by` IS NULL' . ($filter ? ' AND shift_entries.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = TRUE
            ' . ($filter ? 'AND shifts.location_id IN (' . implode(',', $filter->getLocations()) . ')' : '') . '

            UNION

            /* By location */
            SELECT shifts.id, start
            FROM `shifts`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            LEFT JOIN schedules AS se on s.schedule_id = se.id
            WHERE (`end` > ? AND `start` < ?)
            AND (SELECT SUM(`count`) FROM `needed_volunteer_types` WHERE `needed_volunteer_types`.`location_id`=`shifts`.`location_id`' . ($filter ? ' AND needed_volunteer_types.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            > (SELECT COUNT(*) FROM `shift_entries` WHERE `shift_entries`.`shift_id`=`shifts`.`id` AND shift_entries.`freeloaded_by` IS NULL' . ($filter ? ' AND shift_entries.volunteer_type_id IN (' . implode(',', $filter->getTypes()) . ')' : '') . ')
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = FALSE
            ' . ($filter ? 'AND shifts.location_id IN (' . implode(',', $filter->getLocations()) . ')' : '') . '
        ) AS `tmp`
        ORDER BY `tmp`.`start`
        ', [
        $start,
        $end,
        $start,
        $end,
        $start,
        $end,
    ]);

    $shifts = collect($shifts);

    return Shift::with(['location', 'shiftType'])
        ->whereIn('id', $shifts->pluck('id')->toArray())
        ->orderBy('shifts.start')
        ->get();
}

/**
 * @param ShiftsFilter $shiftsFilter
 * @return Shift[]|Collection
 */
function Shifts_by_ShiftsFilter(ShiftsFilter $shiftsFilter)
{
    $sql = '
    SELECT * FROM (
        SELECT DISTINCT `shifts`.*, `shift_types`.`name`, `locations`.`name` AS `location_name`
        FROM `shifts`
        JOIN `locations` ON `shifts`.`location_id` = `locations`.`id`
        JOIN `shift_types` ON `shift_types`.`id` = `shifts`.`shift_type_id`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_id` = `shifts`.`id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
            AND `start` BETWEEN ? AND ?
            AND `needed_volunteer_types`.`volunteer_type_id` IN (' . implode(',', $shiftsFilter->getTypes()) . ')
            AND s.shift_id IS NULL

        UNION

        /* By shift type */
        SELECT DISTINCT `shifts`.*, `shift_types`.`name`, `locations`.`name` AS `location_name`
        FROM `shifts`
        JOIN `locations` ON `shifts`.`location_id` = `locations`.`id`
        JOIN `shift_types` ON `shift_types`.`id` = `shifts`.`shift_type_id`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_type_id`=`shifts`.`shift_type_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
            AND `start` BETWEEN ? AND ?
            AND `needed_volunteer_types`.`volunteer_type_id` IN (' . implode(',', $shiftsFilter->getTypes()) . ')
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = TRUE

        UNION

        /* By location */
        SELECT DISTINCT `shifts`.*, `shift_types`.`name`, `locations`.`name` AS `location_name`
        FROM `shifts`
        JOIN `locations` ON `shifts`.`location_id` = `locations`.`id`
        JOIN `shift_types` ON `shift_types`.`id` = `shifts`.`shift_type_id`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`location_id`=`shifts`.`location_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
            AND `start` BETWEEN ? AND ?
            AND `needed_volunteer_types`.`volunteer_type_id` IN (' . implode(',', $shiftsFilter->getTypes()) . ')
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = FALSE
    ) AS tmp_shifts

    ORDER BY `location_name`, `start`
    ';

    $shiftsData = Db::select(
        $sql,
        [
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
        ]
    );

    $shifts = new Collection();
    foreach ($shiftsData as $shift) {
        $shifts[] = (new Shift())->forceFill($shift);
    }

    $shifts->load(['location', 'shiftType', 'shiftEntries.volunteerType']);

    return $shifts;
}

/**
 * @param ShiftsFilter $shiftsFilter
 * @return array[]
 */
function NeededVolunteertypes_by_ShiftsFilter(ShiftsFilter $shiftsFilter)
{
    $sql = '
        SELECT
            `needed_volunteer_types`.*,
            `shifts`.`id` AS shift_id,
            `volunteer_types`.`id`,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_id`=`shifts`.`id`
        JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
        AND shifts.`start` BETWEEN ? AND ?
        AND s.shift_id IS NULL

        UNION

        /* By shift type */
        SELECT
            `needed_volunteer_types`.*,
            `shifts`.`id` AS shift_id,
            `volunteer_types`.`id`,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_type_id`=`shifts`.`shift_type_id`
        JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
        AND shifts.`start` BETWEEN ? AND ?
        AND NOT s.shift_id IS NULL
        AND se.needed_from_shift_type = TRUE

        UNION

        /* By location */
        SELECT
            `needed_volunteer_types`.*,
            `shifts`.`id` AS shift_id,
            `volunteer_types`.`id`,
            `volunteer_types`.`name`,
            `volunteer_types`.`restricted`,
            `volunteer_types`.`shift_self_signup`
        FROM `shifts`
        JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`location_id`=`shifts`.`location_id`
        JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
        LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
        LEFT JOIN schedules AS se on s.schedule_id = se.id
        WHERE `shifts`.`location_id` IN (' . implode(',', $shiftsFilter->getLocations()) . ')
        AND shifts.`start` BETWEEN ? AND ?
        AND NOT s.shift_id IS NULL
        AND se.needed_from_shift_type = FALSE
    ';

    return Db::select(
        $sql,
        [
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
            $shiftsFilter->getStart(),
            $shiftsFilter->getEnd(),
        ]
    );
}

/**
 * @param Shift     $shift
 * @param VolunteerType $volunteertype
 * @return array|null
 */
function NeededVolunteertype_by_Shift_and_Volunteertype(Shift $shift, VolunteerType $volunteertype)
{
    return Db::selectOne(
        '
            SELECT
                `needed_volunteer_types`.*,
                `shifts`.`id` AS shift_id,
                `volunteer_types`.`id`,
                `volunteer_types`.`name`,
                `volunteer_types`.`restricted`,
                `volunteer_types`.`shift_self_signup`
            FROM `shifts`
            JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_id`=`shifts`.`id`
            JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            WHERE `shifts`.`id`=?
            AND `volunteer_types`.`id`=?
            AND s.shift_id IS NULL

            UNION

            /* By shift type */
            SELECT
                `needed_volunteer_types`.*,
                `shifts`.`id` AS shift_id,
                `volunteer_types`.`id`,
                `volunteer_types`.`name`,
                `volunteer_types`.`restricted`,
                `volunteer_types`.`shift_self_signup`
            FROM `shifts`
            JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`shift_type_id`=`shifts`.`shift_type_id`
            JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            LEFT JOIN schedules AS se on s.schedule_id = se.id
            WHERE `shifts`.`id`=?
            AND `volunteer_types`.`id`=?
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = TRUE

            UNION

            /* By location */
            SELECT
                `needed_volunteer_types`.*,
                `shifts`.`id` AS shift_id,
                `volunteer_types`.`id`,
                `volunteer_types`.`name`,
                `volunteer_types`.`restricted`,
                `volunteer_types`.`shift_self_signup`
            FROM `shifts`
            JOIN `needed_volunteer_types` ON `needed_volunteer_types`.`location_id`=`shifts`.`location_id`
            JOIN `volunteer_types` ON `volunteer_types`.`id`= `needed_volunteer_types`.`volunteer_type_id`
            LEFT JOIN schedule_shift AS s on shifts.id = s.shift_id
            LEFT JOIN schedules AS se on s.schedule_id = se.id
            WHERE `shifts`.`id`=?
            AND `volunteer_types`.`id`=?
            AND NOT s.shift_id IS NULL
            AND se.needed_from_shift_type = FALSE
        ',
        [
            $shift->id,
            $volunteertype->id,
            $shift->id,
            $volunteertype->id,
            $shift->id,
            $volunteertype->id,
        ]
    );
}

/**
 * @param ShiftsFilter $shiftsFilter
 * @return ShiftEntry[]|Collection
 */
function ShiftEntries_by_ShiftsFilter(ShiftsFilter $shiftsFilter)
{
    return ShiftEntry::with('user', 'user.state')
        ->join('shifts', 'shifts.id', 'shift_entries.shift_id')
        ->whereIn('shifts.location_id', $shiftsFilter->getLocations())
        ->whereBetween('start', [$shiftsFilter->getStart(), $shiftsFilter->getEnd()])
        ->get();
}

/**
 * Check if a shift collides with other shifts (in time).
 *
 * @param Shift              $shift
 * @param Shift[]|Collection $shifts
 * @return bool
 */
function Shift_collides(Shift $shift, $shifts)
{
    foreach ($shifts as $other_shift) {
        if ($shift->id != $other_shift->id) {
            if (
                !(
                    $shift->start->timestamp >= $other_shift->end->timestamp
                    || $shift->end->timestamp <= $other_shift->start->timestamp
                )
            ) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Returns the number of needed volunteers/free shift entries for an volunteertype.
 *
 * @param VolunteerType               $needed_volunteertype
 * @param ShiftEntry[]|Collection $shift_entries
 * @return int
 */
function Shift_free_entries(VolunteerType $needed_volunteertype, $shift_entries)
{
    $taken = 0;
    foreach ($shift_entries as $shift_entry) {
        if (!$shift_entry->freeloaded_by) {
            $taken++;
        }
    }

    $neededVolunteers = $needed_volunteertype->count ?: 0;
    return max(0, $neededVolunteers - $taken);
}

/**
 * Check if shift signup is allowed from the end users point of view (no admin like privileges)
 *
 * @param User                    $user
 * @param Shift                   $shift The shift
 * @param VolunteerType               $volunteertype The volunteertype to which the user wants to sign up
 * @param array|null              $user_volunteertype
 * @param Shift[]|Collection|null $user_shifts List of the users shifts
 * @param VolunteerType               $needed_volunteertype
 * @param ShiftEntry[]|Collection $shift_entries
 * @return ShiftSignupState
 */
function Shift_signup_allowed_volunteer(
    $user,
    Shift $shift,
    VolunteerType $volunteertype,
    $user_volunteertype,
    $user_shifts,
    VolunteerType $needed_volunteertype,
    $shift_entries
) {
    $free_entries = Shift_free_entries($needed_volunteertype, $shift_entries);

    if (is_null($user_shifts) || $user_shifts->isEmpty()) {
        $user_shifts = Shifts_by_user($user->id);
    }

    $signed_up = false;
    foreach ($user_shifts as $user_shift) {
        if ($user_shift->id == $shift->id) {
            $signed_up = true;
            break;
        }
    }

    if ($signed_up) {
        // you cannot join if you already signed up for this shift
        return new ShiftSignupState(ShiftSignupStatus::SIGNED_UP, $free_entries);
    }

    $shift_post_signup_total_allowed_seconds =
        (config('signup_post_fraction') * ($shift->end->timestamp - $shift->start->timestamp))
        + (config('signup_post_minutes') * 60);

    if (time() > $shift->start->timestamp + $shift_post_signup_total_allowed_seconds) {
        // you can only join if the shift is in future
        return new ShiftSignupState(ShiftSignupStatus::SHIFT_ENDED, $free_entries);
    }
    if ($free_entries == 0) {
        // you cannot join if shift is full
        return new ShiftSignupState(ShiftSignupStatus::OCCUPIED, $free_entries);
    }

    if (empty($user_volunteertype)) {
        $user_volunteertype = UserVolunteerType::whereUserId($user->id)->where('volunteer_type_id', $volunteertype->id)->first();
    }

    if (
        empty($user_volunteertype)
        || !$volunteertype->shift_self_signup
        || ($volunteertype->restricted && !isset($user_volunteertype['confirm_user_id']))
    ) {
        // you cannot join if user is not of this volunteer type
        // you cannot join if volunteertype has shift self signup disabled
        // you cannot join if you are not confirmed

        return new ShiftSignupState(ShiftSignupStatus::VOLUNTEERTYPE, $free_entries);
    }

    if (Shift_collides($shift, $user_shifts)) {
        // you cannot join if user already joined a parallel of this shift
        return new ShiftSignupState(ShiftSignupStatus::COLLIDES, $free_entries);
    }

    $signupAdvanceHours = $shift->shiftType->signup_advance_hours ?: config('signup_advance_hours');
    if ($signupAdvanceHours && $shift->start->timestamp > time() + $signupAdvanceHours * 3600) {
        return new ShiftSignupState(ShiftSignupStatus::NOT_YET, $free_entries);
    }

    if (config('signup_requires_arrival') && !$user->state->arrived) {
        return new ShiftSignupState(ShiftSignupStatus::NOT_ARRIVED, $free_entries);
    }

    // Hooray, shift is free for you!
    return new ShiftSignupState(ShiftSignupStatus::FREE, $free_entries);
}

/**
 * Check if an volunteertype supporter can sign up a user to a shift.
 *
 * @param VolunteerType               $needed_volunteertype
 * @param ShiftEntry[]|Collection $shift_entries
 * @return ShiftSignupState
 */
function Shift_signup_allowed_volunteertype_supporter(VolunteerType $needed_volunteertype, $shift_entries)
{
    $free_entries = Shift_free_entries($needed_volunteertype, $shift_entries);
    if ($free_entries == 0) {
        return new ShiftSignupState(ShiftSignupStatus::OCCUPIED, $free_entries);
    }

    return new ShiftSignupState(ShiftSignupStatus::FREE, $free_entries);
}

/**
 * Check if an admin can sign up a user to a shift.
 *
 * @param VolunteerType               $needed_volunteertype
 * @param ShiftEntry[]|Collection $shift_entries
 * @return ShiftSignupState
 */
function Shift_signup_allowed_admin(VolunteerType $needed_volunteertype, $shift_entries)
{
    $free_entries = Shift_free_entries($needed_volunteertype, $shift_entries);

    if ($free_entries == 0) {
        // User shift admins may join anybody in every shift
        return new ShiftSignupState(ShiftSignupStatus::ADMIN, $free_entries);
    }

    return new ShiftSignupState(ShiftSignupStatus::FREE, $free_entries);
}

/**
 * Check if an volunteer can sign out from a shift.
 *
 * @param Shift     $shift The shift
 * @param VolunteerType $volunteertype The volunteertype
 * @param int       $signout_user_id The user that was signed up for the shift
 * @param ?bool     $isVolunteertypeSupporter User is supporter for volunteertype
 * @return bool
 */
function Shift_signout_allowed(Shift $shift, VolunteerType $volunteertype, $signout_user_id, ?bool $isVolunteertypeSupporter = null)
{
    $user = auth()->user();

    // user shifts admin can sign out any user at any time
    if (auth()->can('user_shifts_admin')) {
        return true;
    }

    // volunteertype supporter can sign out any user at any time from their supported volunteertype
    $isVolunteertypeSupporter = !is_null($isVolunteertypeSupporter)
        ? $isVolunteertypeSupporter
        : $user->isVolunteerTypeSupporter($volunteertype);
    if ($isVolunteertypeSupporter || auth()->can('admin_user_volunteertypes')) {
        return true;
    }

    if ($signout_user_id == $user->id && $shift->start->subHours(config('last_unsubscribe')) > Carbon::now()) {
        return true;
    }

    return false;
}

/**
 * Check if an volunteer can sign up for given shift.
 *
 * @param User                    $signup_user
 * @param Shift                   $shift The shift
 * @param VolunteerType               $volunteertype The volunteertype to which the user wants to sign up
 * @param array|null              $user_volunteertype
 * @param Shift[]|Collection|null $user_shifts List of the users shifts
 * @param VolunteerType               $needed_volunteertype
 * @param ShiftEntry[]|Collection $shift_entries
 * @return ShiftSignupState
 */
function Shift_signup_allowed(
    $signup_user,
    Shift $shift,
    VolunteerType $volunteertype,
    $user_volunteertype,
    $user_shifts,
    VolunteerType $needed_volunteertype,
    $shift_entries
) {
    if (auth()->can('user_shifts_admin')) {
        return Shift_signup_allowed_admin($needed_volunteertype, $shift_entries);
    }

    if (
        auth()->user()->isVolunteerTypeSupporter($volunteertype) || auth()->can('admin_user_volunteertypes')
    ) {
        return Shift_signup_allowed_volunteertype_supporter($needed_volunteertype, $shift_entries);
    }

    return Shift_signup_allowed_volunteer(
        $signup_user,
        $shift,
        $volunteertype,
        $user_volunteertype,
        $user_shifts,
        $needed_volunteertype,
        $shift_entries
    );
}

/**
 * Return users shifts.
 *
 * @param int  $userId
 * @param bool $include_freeloaded_comments
 * @return SupportCollection|Shift[]
 */
function Shifts_by_user($userId, $include_freeloaded_comments = false)
{
    # Cache static content per request
    static $cached;
    if (!empty($cached[$userId][$include_freeloaded_comments])) {
        return $cached[$userId][$include_freeloaded_comments];
    }

    $shiftsData = Db::select(
        '
        SELECT
            `locations`.*,
            `locations`.name AS Name,
            `shift_types`.`id` AS `shifttype_id`,
            `shift_types`.`name`,
            `shift_entries`.`id` as shift_entry_id,
            `shift_entries`.`shift_id`,
            `shift_entries`.`volunteer_type_id`,
            `shift_entries`.`user_id`,
            `shift_entries`.`freeloaded_by`,
            `shift_entries`.`user_comment`,
            ' . ($include_freeloaded_comments ? '`shift_entries`.`freeloaded_comment`, ' : '') . '
            `shifts`.*
        FROM `shift_entries`
        JOIN `shifts` ON (`shift_entries`.`shift_id` = `shifts`.`id`)
        JOIN `shift_types` ON (`shift_types`.`id` = `shifts`.`shift_type_id`)
        JOIN `locations` ON (`shifts`.`location_id` = `locations`.`id`)
        WHERE shift_entries.`user_id` = ?
        GROUP BY shifts.id
        ORDER BY `start`
        ',
        [
            $userId,
        ]
    );

    $shifts = new Collection();
    foreach ($shiftsData as $data) {
        $shifts[] = (new Shift())->forceFill($data);
    }
    $shifts->load(['shiftType', 'location']);
    $cached[$userId][$include_freeloaded_comments] = $shifts;

    return $shifts;
}

/**
 * Returns Shift by id or extends existing Shift
 *
 * @param int|Shift $shift Shift ID or shift model
 * @return Shift|null
 */
function Shift($shift)
{
    if (!$shift instanceof Shift) {
        $shift = Shift::find($shift);
    }

    if (!$shift) {
        return null;
    }

    $neededVolunteers = [];
    $volunteerTypes = NeededVolunteerTypes_by_shift($shift);
    foreach ($volunteerTypes as $type) {
        $neededVolunteers[] = [
            'volunteer_type_id' => $type['volunteer_type_id'],
            'count'         => $type['count'],
            'restricted'    => $type['restricted'],
            'taken'         => $type['taken'],
        ];
    }
    $shift->neededVolunteers = $neededVolunteers;

    return $shift;
}
