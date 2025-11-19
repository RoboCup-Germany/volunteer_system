<?php

use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\ShiftsFilter;
use Volunteersystem\ShiftsFilterRenderer;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * Text for Volunteertype related links.
 *
 * @return string
 */
function volunteertypes_title()
{
    return __('volunteertypes.volunteertypes');
}

/**
 * Route volunteertype actions.
 *
 * @return array
 */
function volunteertypes_controller()
{
    $action = strip_request_item('action', 'list');

    return match ($action) {
        'view'   => volunteertype_controller(),
        'edit'   => volunteertype_edit_controller(),
        'delete' => volunteertype_delete_controller(),
        'list'   => volunteertypes_list_controller(),
        default  => volunteertypes_list_controller(),
    };
}

/**
 * Path to volunteertype view.
 *
 * @param int   $volunteertype_id VolunteerType id
 * @param array $params       additional params
 * @return string
 */
function volunteertype_link($volunteertype_id, $params = [])
{
    $params = array_merge(['action' => 'view', 'volunteertype_id' => $volunteertype_id], $params);
    return url('/volunteertypes', $params);
}

/**
 * Delete an Volunteertype.
 *
 * @return array
 */
function volunteertype_delete_controller()
{
    if (!auth()->can('admin_volunteer_types')) {
        throw_redirect(url('/volunteertypes'));
    }

    $volunteertype = VolunteerType::findOrFail(request()->input('volunteertype_id'));

    if (request()->hasPostData('delete')) {
        $volunteertype->delete();
        volunteersystem_log('Deleted volunteer type: ' . VolunteerType_name_render($volunteertype, true));
        success(sprintf(__('Volunteer type %s deleted.'), $volunteertype->name));
        throw_redirect(url('/volunteertypes'));
    }

    return [
        sprintf(__('Delete volunteer type %s'), htmlspecialchars($volunteertype->name)),
        VolunteerType_delete_view($volunteertype),
    ];
}

/**
 * Change an Volunteertype.
 *
 * @return array
 */
function volunteertype_edit_controller()
{
    // In supporter mode only allow to modify description
    $supporter_mode = !auth()->can('admin_volunteer_types');
    $request = request();

    if ($request->has('volunteertype_id')) {
        // Edit existing volunteertype
        $volunteertype = VolunteerType::findOrFail($request->input('volunteertype_id'));

        if (!auth()->user()?->isVolunteerTypeSupporter($volunteertype) && !auth()->can('admin_user_volunteertypes')) {
            throw_redirect(url('/volunteertypes'));
        }
    } else {
        // New volunteertype
        if ($supporter_mode) {
            // Supporters aren't allowed to create new volunteertypes.
            throw_redirect(url('/volunteertypes'));
        }
        $volunteertype = new VolunteerType();
    }

    if ($request->hasPostData('submit')) {
        $valid = true;

        if (!$supporter_mode) {
            if ($request->has('name')) {
                $name = substr(strip_item($request->postData('name')), 0, 255);
                $valid = VolunteerType_validate_name($request->postData('name'), $volunteertype);
                $volunteertype->name = $name;
                if (!$valid) {
                    error(__('Please check the name. Maybe it already exists.'));
                }
            }

            $volunteertype->restricted = $request->has('restricted');
            $volunteertype->shift_self_signup = $request->has('shift_self_signup');
            $volunteertype->show_on_dashboard = $request->has('show_on_dashboard');
            $volunteertype->hide_register = $request->has('hide_register');
            $volunteertype->hide_on_shift_view = $request->has('hide_on_shift_view');

            $volunteertype->requires_driver_license = $request->has('requires_driver_license');
            $volunteertype->requires_ifsg_certificate = $request->has('requires_ifsg_certificate');
        }

        $volunteertype->description = strip_request_item_nl('description', $volunteertype->description);

        $volunteertype->contact_name = strip_request_item('contact_name', $volunteertype->contact_name);
        $volunteertype->contact_dect = strip_request_item('contact_dect', $volunteertype->contact_dect) ?: '';
        $volunteertype->contact_email = strip_request_item('contact_email', $volunteertype->contact_email);

        if ($valid) {
            $volunteertype->save();

            success(__('Volunteer type saved.'));
            volunteersystem_log(
                'Saved volunteer type: ' . $volunteertype->name . ($volunteertype->restricted ? ', restricted' : '')
                . ($volunteertype->shift_self_signup ? ', shift_self_signup' : '')
                . (config('driving_license_enabled')
                    ? (($volunteertype->requires_driver_license ? ', requires driver license' : '') . ', ')
                    : '')
                . (config('ifsg_enabled')
                    ? (($volunteertype->requires_ifsg_certificate ? ', requires ifsg certificate' : '') . ', ')
                    : '')
                . $volunteertype->contact_name . ', '
                . $volunteertype->contact_dect . ', '
                . $volunteertype->contact_email . ', '
                . $volunteertype->show_on_dashboard . ', '
                . $volunteertype->hide_register . ', '
                . $volunteertype->hide_on_shift_view
            );
            throw_redirect(volunteertype_link($volunteertype->id));
        }
    }

    return [
        sprintf(__('Edit %s'), htmlspecialchars((string) $volunteertype->name)),
        VolunteerType_edit_view($volunteertype, $supporter_mode),
    ];
}

/**
 * View details of a given volunteertype.
 *
 * @return array
 */
function volunteertype_controller()
{
    $user = auth()->user();

    if (!auth()->can('volunteertypes')) {
        throw_redirect(url('/'));
    }

    $volunteertype = VolunteerType::findOrFail(request()->input('volunteertype_id'));
    /** @var UserVolunteerType $user_volunteertype */
    $user_volunteertype = UserVolunteerType::whereUserId($user->id)->where('volunteer_type_id', $volunteertype->id)->first();
    $members = $volunteertype->userVolunteerTypes
        ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
        ->load(['state', 'personalData', 'contact']);
    $days = volunteertype_controller_shiftsFilterDays($volunteertype);
    $shiftsFilter = volunteertype_controller_shiftsFilter($volunteertype, $days);
    if (request()->input('showFilledShifts')) {
        $shiftsFilter->setFilled([ShiftsFilter::FILLED_FREE, ShiftsFilter::FILLED_FILLED]);
    }

    $shiftsFilterRenderer = new ShiftsFilterRenderer($shiftsFilter);
    $shiftsFilterRenderer->enableDaySelection($days);

    $shiftCalendarRenderer = shiftCalendarRendererByShiftFilter($shiftsFilter);
    $request = request();
    $tab = 0;

    if ($request->has('shifts_filter_day') || $request->has('showShiftsTab')) {
        $tab = 1;
    }

    $isSupporter = !is_null($user_volunteertype) && $user_volunteertype->supporter;
    return [
        sprintf(__('Team %s'), htmlspecialchars($volunteertype->name)),
        VolunteerType_view(
            $volunteertype,
            $members,
            $user_volunteertype,
            auth()->can('admin_user_volunteertypes') || $isSupporter,
            auth()->can('admin_volunteer_types'),
            $isSupporter,
            $user->license,
            $user,
            $shiftsFilterRenderer,
            $shiftCalendarRenderer,
            $tab
        ),
    ];
}

/**
 * On which days do shifts for this volunteertype occur? Needed for shiftCalendar.
 *
 * @param VolunteerType $volunteertype
 * @return array
 */
function volunteertype_controller_shiftsFilterDays(VolunteerType $volunteertype)
{
    $all_shifts = Shifts_by_volunteertype($volunteertype);
    $days = [];
    foreach ($all_shifts as $shift) {
        $day = Carbon::make($shift['start'])->format('Y-m-d');
        if (!isset($days[$day])) {
            $days[$day] = dateWithEventDay($day);
        }
    }
    ksort($days);
    return $days;
}

/**
 * Sets up the shift filter for the volunteertype.
 *
 * @param VolunteerType $volunteertype
 * @param array     $days
 * @return ShiftsFilter
 */
function volunteertype_controller_shiftsFilter(VolunteerType $volunteertype, $days)
{
    $request = request();
    $locationIds = Location::query()
        ->select('id')
        ->pluck('id')
        ->toArray();
    $shiftsFilter = new ShiftsFilter(
        auth()->can('user_shifts_admin'),
        $locationIds,
        [$volunteertype->id]
    );
    $selected_day = date('Y-m-d');
    if (!empty($days) && !isset($days[$selected_day])) {
        $selected_day = array_key_first($days);
    }
    if ($request->input('shifts_filter_day')) {
        $selected_day = $request->input('shifts_filter_day');
    }
    $shiftsFilter->setStartTime(parse_date('Y-m-d H:i', $selected_day . ' 00:00'));
    $shiftsFilter->setEndTime(parse_date('Y-m-d H:i', $selected_day . ' 23:59'));

    return $shiftsFilter;
}

/**
 * View a list of all volunteertypes.
 *
 * @return array
 */
function volunteertypes_list_controller()
{
    $user = auth()->user();
    $admin_volunteertypes = auth()->can('admin_volunteer_types');

    if (!auth()->can('volunteertypes')) {
        throw_redirect(url('/'));
    }

    $volunteertypes = VolunteerTypes_with_user($user->id);
    foreach ($volunteertypes as $volunteertype) {
        $actions = [
            button(
                url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]),
                icon('eye') . ($admin_volunteertypes ? '' : __('form.view')),
                'btn-sm btn-info',
                '',
                ($admin_volunteertypes ? __('form.view') : '')
            ),
        ];

        if ($admin_volunteertypes) {
            $actions[] = button(
                url('/volunteertypes', ['action' => 'edit', 'volunteertype_id' => $volunteertype->id]),
                icon('pencil'),
                'btn-sm',
                '',
                __('form.edit')
            );
            $actions[] = button(
                url('/volunteertypes', ['action' => 'delete', 'volunteertype_id' => $volunteertype->id]),
                icon('trash'),
                'btn-sm btn-danger',
                '',
                __('form.delete')
            );
        }

        $volunteertype->membership = VolunteerType_render_membership($volunteertype);
        if (!empty($volunteertype->user_volunteer_type_id)) {
            $actions[] = button(
                url(
                    '/user-volunteertypes',
                    ['action' => 'delete', 'user_volunteertype_id' => $volunteertype->user_volunteer_type_id]
                ),
                icon('box-arrow-right') . ($admin_volunteertypes ? '' : __('Leave')),
                'btn-sm',
                '',
                ($admin_volunteertypes ? __('Leave') : '')
            );
        } else {
            $actions[] = button(
                url('/user_volunteertypes', ['action' => 'add', 'volunteertype_id' => $volunteertype->id]),
                icon('box-arrow-in-right') . ($admin_volunteertypes ? '' : __('Join')),
                'btn-sm' . ($admin_volunteertypes ? ' btn-success' : ''),
                '',
                ($admin_volunteertypes ? __('Join') : '')
            );
        }

        $volunteertype->is_restricted = $volunteertype->restricted ? icon('mortarboard-fill') : '';
        $volunteertype->shift_self_signup_allowed = $volunteertype->shift_self_signup ? icon('pencil-square') : '';

        $volunteertype->name = '<a href="'
            . url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id])
            . '">'
            . htmlspecialchars($volunteertype->name)
            . '</a>';

        $volunteertype->actions = table_buttons($actions);
    }

    return [
        volunteertypes_title(),
        VolunteerTypes_list_view($volunteertypes, auth()->can('admin_volunteer_types')),
    ];
}

/**
 * Validates a name for volunteertypes.
 *
 * @param string    $name Wanted name for the volunteertype
 * @param VolunteerType $volunteertype The volunteertype the name is for
 *
 * @return bool validation result
 */
function VolunteerType_validate_name($name, VolunteerType $volunteertype)
{
    if ($name == '') {
        return false;
    }
    if ($volunteertype->id) {
        return VolunteerType::whereName($name)
                ->where('id', '!=', $volunteertype->id)
                ->count() == 0;
    }

    return VolunteerType::whereName($name)->count() == 0;
}

/**
 * Returns all volunteertypes and subscription state to each of them for given user.
 *
 * @param int $userId
 * @return Collection|VolunteerType[]
 */
function VolunteerTypes_with_user($userId): Collection
{
    return VolunteerType::query()
        ->select([
            'volunteer_types.*',
            'user_volunteer_type.id AS user_volunteer_type_id',
            'user_volunteer_type.confirm_user_id',
            'user_volunteer_type.supporter',
        ])
        ->leftJoin('user_volunteer_type', function (JoinClause $join) use ($userId) {
            $join->on('volunteer_types.id', 'user_volunteer_type.volunteer_type_id');
            $join->where('user_volunteer_type.user_id', $userId);
        })
        ->get();
}
