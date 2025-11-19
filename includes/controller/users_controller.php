<?php

use Volunteersystem\Database\Db;
use Volunteersystem\Helpers\Goodie;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\ShiftCalendarRenderer;
use Volunteersystem\ShiftsFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Route user actions.
 *
 * @return array
 */
function users_controller()
{
    $user = auth()->user();
    $request = request();

    if (!$user) {
        throw_redirect(url('/'));
    }

    $action = 'list';
    if ($request->has('action')) {
        $action = $request->input('action');
    }

    return match ($action) {
        'view'          => user_controller(),
        'delete'        => user_delete_controller(),
        'list'          => users_list_controller(),
        default         => users_list_controller(),
    };
}

/**
 * Delete a user, requires to enter own password for reasons.
 *
 * @return array
 */
function user_delete_controller()
{
    $user = auth()->user();
    $auth = auth();
    $request = request();

    if ($request->has('user_id')) {
        $user_source = User::findOrFail($request->query->get('user_id'));
    } else {
        $user_source = $user;
    }

    if (!auth()->can('admin_user')) {
        throw_redirect(url('/'));
    }

    // You cannot delete yourself
    if ($user->id == $user_source->id) {
        error(__('You cannot delete yourself.'));
        throw_redirect(user_link($user->id));
    }

    if ($request->hasPostData('submit')) {
        $valid = true;

        if (
            !(
            $request->has('password')
            && $auth->verifyPassword($user, $request->postData('password'))
            )
        ) {
            $valid = false;
            error(__('auth.password.error'));
        }

        if ($valid) {
            // Move user created news/answers/worklogs/shifts  etc. to deleting user
            $user_source->news()->update(['user_id' => $user->id]);
            $user_source->questionsAnswered()->update(['answerer_id' => $user->id]);
            $user_source->worklogsCreated()->update(['creator_id' => $user->id]);
            $user_source->shiftsCreated()->update(['created_by' => $user->id]);
            $user_source->shiftsUpdated()->update(['updated_by' => $user->id]);

            // Load data before user deletion to prevent errors when displaying
            $user_source->load(['contact', 'personalData', 'settings', 'state']);
            $user_source->delete();

            mail_user_delete($user_source);
            success(__('User deleted.'));
            volunteersystem_log(sprintf('Deleted user %s', User_Nick_render($user_source, true)));

            throw_redirect(users_link());
        }
    }

    return [
        sprintf(__('Delete %s'), htmlspecialchars($user_source->displayName)),
        User_delete_view($user_source),
    ];
}

/**
 * @return string
 */
function users_link()
{
    return url('/users');
}

/**
 * @param int $userId
 * @return string
 */
function user_edit_link($userId)
{
    return url('/admin-user', ['user_id' => $userId]);
}

/**
 * @param int $userId
 * @return string
 */
function user_delete_link($userId)
{
    return url('/users', ['action' => 'delete', 'user_id' => $userId]);
}

/**
 * @param int $userId
 * @return string
 */
function user_link($userId)
{
    return url('/users', ['action' => 'view', 'user_id' => $userId]);
}

/**
 * @return array
 */
function user_controller()
{
    $user = auth()->user();
    $request = request();

    $user_source = $user;
    if ($request->has('user_id')) {
        $user_source = User::find($request->input('user_id'));
        if (!$user_source) {
            error(__('User not found.'));
            throw_redirect(url('/'));
        }
    }

    $shifts = Shifts_by_user($user_source->id, true);
    foreach ($shifts as $shift) {
        // TODO: Move queries to model
        $shift->needed_volunteertypes = Db::select(
            '
            SELECT DISTINCT `volunteer_types`.*
            FROM `shift_entries`
            JOIN `volunteer_types` ON `shift_entries`.`volunteer_type_id`=`volunteer_types`.`id`
            WHERE `shift_entries`.`shift_id` = ?
            ORDER BY `volunteer_types`.`name`
            ',
            [$shift->id]
        );
        $neededVolunteertypes = $shift->needed_volunteertypes;
        foreach ($neededVolunteertypes as &$needed_volunteertype) {
            $needed_volunteertype['users'] = User::query()
                ->select(['users.*', 'shift_entries.freeloaded_by'])
                ->from('shift_entries')
                ->join('users', 'shift_entries.user_id', 'users.id')
                ->where('shift_entries.shift_id', $shift->id)
                ->where('shift_entries.volunteer_type_id', $needed_volunteertype['id'])
                ->with('state')
                ->get();
        }
        $shift->needed_volunteertypes = $neededVolunteertypes;
    }

    if (empty($user_source->api_key)) {
        auth()->resetApiKey($user_source);
    }

    $goodie_score = sprintf('%.2f', Goodie::userScore($user_source)) . '&nbsp;h';
    if ($user_source->state->force_active && config('enable_force_active')) {
        $goodie_score = '<span title="' . $goodie_score . '">' . __('user.goodie_score.enough') . '</span>';
    }

    $worklogs = $user_source->worklogs()
        ->with(['user', 'creator'])
        ->get();

    $is_ifsg_supporter = (bool) VolunteerType::whereRequiresIfsgCertificate(true)
        ->leftJoin('user_volunteer_type', 'user_volunteer_type.volunteer_type_id', 'volunteer_types.id')
        ->where('user_volunteer_type.user_id', $user->id)
        ->where('user_volunteer_type.supporter', true)
        ->count();

    $is_drive_supporter = (bool) VolunteerType::whereRequiresDriverLicense(true)
        ->leftJoin('user_volunteer_type', 'user_volunteer_type.volunteer_type_id', 'volunteer_types.id')
        ->where('user_volunteer_type.user_id', $user->id)
        ->where('user_volunteer_type.supporter', true)
        ->count();

    return [
        htmlspecialchars($user_source->displayName),
        User_view(
            $user_source,
            auth()->can('admin_user'),
            $user_source->isFreeloader(),
            $user_source->userVolunteerTypes,
            $user_source->groups,
            $shifts,
            $user->id == $user_source->id,
            $goodie_score,
            auth()->can('user.goodie.edit'),
            auth()->can('admin_user_worklog'),
            $worklogs,
            auth()->can('user.ifsg.edit')
                || $is_ifsg_supporter
                || auth()->can('user.drive.edit')
                || $is_drive_supporter,
        ),
    ];
}

/**
 * List all users.
 *
 * @return array
 */
function users_list_controller()
{
    $request = request();

    if (!auth()->can('admin_user')) {
        throw_redirect(url('/'));
    }

    $order_by = 'name';
    if (
        $request->has('OrderBy') && in_array($request->input('OrderBy'), [
            'name',
            'first_name',
            'last_name',
            'dect',
            'arrived',
            'got_voucher',
            'freeloads',
            'active',
            'force_active',
            'force_food',
            'got_goodie',
            'shirt_size',
            'planned_arrival_date',
            'planned_departure_date',
            'last_login_at',
        ])
    ) {
        $order_by = $request->input('OrderBy');
    }

    /** @var User[]|Collection $users */
    $users = User::with(['contact', 'personalData', 'state', 'shiftEntries' => function (HasMany $query) {
        $query->whereNotNull('freeloaded_by');
    }])
        ->orderBy('name')
        ->get();
    foreach ($users as $user) {
        $user->setAttribute(
            'freeloads',
            $user->shiftEntries
                ->whereNotNull('freeloaded_by')
                ->count()
        );
    }

    $users = $users->sortBy(function (User $user) use ($order_by) {
        $userData = $user->toArray();
        $data = [];
        array_walk_recursive($userData, function ($value, $key) use (&$data) {
            $data[$key] = $value;
        });

        return isset($data[$order_by]) ? Str::lower($data[$order_by]) : null;
    });

    return [
        __('All users'),
        Users_view(
            $users,
            $order_by,
            State::whereArrived(true)->count(),
            State::whereActive(true)->count(),
            State::whereForceActive(true)->count(),
            State::whereForceFood(true)->count(),
            ShiftEntry::whereNotNull('freeloaded_by')->count(),
            State::whereGotGoodie(true)->count(),
            State::query()->sum('got_voucher'),
            auth()->can('admin_user'),
        ),
    ];
}

/**
 * Loads a user from param user_id.
 *
 * @return User
 */
function load_user()
{
    $request = request();
    if (!$request->has('user_id')) {
        throw_redirect(url('/'));
    }

    $user = User::find($request->input('user_id'));
    if (!$user) {
        error(__('User doesn\'t exist.'));
        throw_redirect(url('/'));
    }

    return $user;
}

/**
 * @param ShiftsFilter $shiftsFilter
 * @return ShiftCalendarRenderer
 */
function shiftCalendarRendererByShiftFilter(ShiftsFilter $shiftsFilter)
{
    $shifts = Shifts_by_ShiftsFilter($shiftsFilter);
    $needed_volunteertypes_source = NeededVolunteertypes_by_ShiftsFilter($shiftsFilter);
    $shift_entries_source = ShiftEntries_by_ShiftsFilter($shiftsFilter);

    $needed_volunteertypes = [];
    /** @var ShiftEntry[][] $shift_entries */
    $shift_entries = [];
    foreach ($shifts as $shift) {
        $needed_volunteertypes[$shift->id] = [];
        $shift_entries[$shift->id] = [];
    }

    foreach ($shift_entries_source as $shift_entry) {
        if (isset($shift_entries[$shift_entry->shift_id])) {
            $shift_entries[$shift_entry->shift_id][] = $shift_entry;
        }
    }

    foreach ($needed_volunteertypes_source as $needed_volunteertype) {
        if (isset($needed_volunteertypes[$needed_volunteertype['shift_id']])) {
            $needed_volunteertypes[$needed_volunteertype['shift_id']][] = $needed_volunteertype;
        }
    }

    unset($needed_volunteertypes_source);
    unset($shift_entries_source);

    if (
        in_array(ShiftsFilter::FILLED_FREE, $shiftsFilter->getFilled())
        && in_array(ShiftsFilter::FILLED_FILLED, $shiftsFilter->getFilled())
    ) {
        return new ShiftCalendarRenderer($shifts, $needed_volunteertypes, $shift_entries, $shiftsFilter);
    }

    $filtered_shifts = [];
    foreach ($shifts as $shift) {
        $needed_volunteers_count = 0;
        foreach ($needed_volunteertypes[$shift->id] as $needed_volunteertype) {
            $taken = 0;

            if (
                !in_array(ShiftsFilter::FILLED_FILLED, $shiftsFilter->getFilled())
                && !in_array($needed_volunteertype['volunteer_type_id'], $shiftsFilter->getTypes())
            ) {
                continue;
            }

            foreach ($shift_entries[$shift->id] as $shift_entry) {
                if (
                    $needed_volunteertype['volunteer_type_id'] == $shift_entry->volunteer_type_id
                    && !$shift_entry->freeloaded_by
                ) {
                    $taken++;
                }
            }

            $needed_volunteers_count += max(0, $needed_volunteertype['count'] - $taken);
        }

        if (
            in_array(ShiftsFilter::FILLED_FREE, $shiftsFilter->getFilled())
            && $needed_volunteers_count > 0
        ) {
            $filtered_shifts[] = $shift;
        }

        if (
            in_array(ShiftsFilter::FILLED_FILLED, $shiftsFilter->getFilled())
            && $needed_volunteers_count == 0
        ) {
            $filtered_shifts[] = $shift;
        }
    }

    return new ShiftCalendarRenderer($filtered_shifts, $needed_volunteertypes, $shift_entries, $shiftsFilter);
}

/**
 * Generates a hint, if user joined volunteertypes that require a driving license and the user has no driver license
 * information provided.
 *
 * @return string|null
 */
function user_driver_license_required_hint()
{
    $user = auth()->user();

    // User has already entered data, no hint needed.
    if (!config('driving_license_enabled') || $user->license->wantsToDrive()) {
        return null;
    }

    $volunteertypes = $user->userVolunteerTypes;
    foreach ($volunteertypes as $volunteertype) {
        if ($volunteertype->requires_driver_license) {
            return sprintf(
                __('volunteertype.driving_license.required.info.here'),
                '<a href="' . url('/settings/certificates') . '">' . __('driving_license.info') . '</a>'
            );
        }
    }

    return null;
}

function user_ifsg_certificate_required_hint()
{
    $user = auth()->user();

    // User has already entered data, no hint needed.
    if (!config('ifsg_enabled') || $user->license->ifsg_light || $user->license->ifsg) {
        return null;
    }

    $volunteertypes = $user->userVolunteerTypes;
    foreach ($volunteertypes as $volunteertype) {
        if (
            $volunteertype->requires_ifsg_certificate && !(
                $user->license->ifsg_certificate || $user->license->ifsg_certificate_light
            )
        ) {
            return sprintf(
                __('volunteertype.ifsg.required.info.here'),
                '<a href="' . url('/settings/certificates') . '">' . __('ifsg.info') . '</a>'
            );
        }
    }

    return null;
}
