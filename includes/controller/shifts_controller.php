<?php

use Volunteersystem\Http\Exceptions\HttpForbidden;
use Volunteersystem\Http\Exceptions\HttpNotFound;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\ScheduleShift;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftSignupStatus;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\ShiftSignupState;
use Illuminate\Support\Str;

/**
 * @param array|Shift $shift
 * @return string
 */
function shift_link($shift)
{
    $parameters = ['action' => 'view'];
    if (isset($shift['shift_id']) || isset($shift['id'])) {
        $parameters['shift_id'] = $shift['shift_id'] ?? $shift['id'];
    }

    return url('/shifts', $parameters);
}

/**
 * @param Shift $shift
 * @return string
 */
function shift_edit_link(Shift $shift)
{
    return url('/user-shifts', ['edit_shift' => $shift->id]);
}

/**
 * Edit a single shift.
 *
 * @return string
 */
function shift_edit_controller()
{
    $valid = true;
    $request = request();

    if (!auth()->can('admin_shifts')) {
        throw_redirect(url('/user-shifts'));
    }

    if (!$request->has('edit_shift') || !test_request_int('edit_shift')) {
        throw_redirect(url('/user-shifts'));
    }
    $shift_id = $request->input('edit_shift');

    $shift = Shift::findOrFail($shift_id);
    if (ScheduleShift::whereShiftId($shift->id)->first()) {
        warning(__(
            'This shift was imported from a schedule so some changes will be overwritten with the next import.'
        ));
    }

    $locations = [];
    foreach (Location::orderBy('name')->get() as $location) {
        $locations[$location->id] = $location->name;
    }
    $volunteertypes = VolunteerType::all()->pluck('name', 'id')->toArray();
    $shifttypes = ShiftType::all()->pluck('name', 'id')->toArray();

    $needed_volunteer_types = collect(NeededVolunteerTypes_by_shift($shift))->pluck('count', 'volunteer_type_id')->toArray();
    foreach (array_keys($volunteertypes) as $volunteertype_id) {
        if (!isset($needed_volunteer_types[$volunteertype_id])) {
            $needed_volunteer_types[$volunteertype_id] = 0;
        }
    }

    $shifttype_id = $shift->shift_type_id;
    $title = $shift->title;
    $description = $shift->description;
    $rid = $shift->location_id;
    $start = $shift->start;
    $end = $shift->end;

    if ($request->hasPostData('submit')) {
        // Name/Bezeichnung der Schicht, darf leer sein
        $title = strip_request_item('title');
        $description = strip_request_item_nl('description');

        // Auswahl der sichtbaren Locations für die Schichten
        if (
            $request->has('rid')
            && preg_match('/^\d+$/', $request->input('rid'))
            && isset($locations[$request->input('rid')])
        ) {
            $rid = $request->input('rid');
        } else {
            $valid = false;
            error(__('Please select a location.'));
        }

        if ($request->has('shifttype_id') && isset($shifttypes[$request->input('shifttype_id')])) {
            $shifttype_id = $request->input('shifttype_id');
        } else {
            $valid = false;
            error(__('Please select a shift type.'));
        }

        if ($request->has('start') && $tmp = DateTime::createFromFormat('Y-m-d\TH:i', $request->input('start'))) {
            $start = $tmp;
        } else {
            $valid = false;
            error(__('Please enter a valid starting time for the shifts.'));
        }

        if ($request->has('end') && $tmp = DateTime::createFromFormat('Y-m-d\TH:i', $request->input('end'))) {
            $end = $tmp;
        } else {
            $valid = false;
            error(__('Please enter a valid ending time for the shifts.'));
        }

        if ($start >= $end) {
            $valid = false;
            error(__('The ending time has to be after the starting time.'));
        }

        foreach ($needed_volunteer_types as $needed_volunteertype_id => $count) {
            $needed_volunteer_types[$needed_volunteertype_id] = 0;

            $queryKey = 'volunteertype_count_' . $needed_volunteertype_id;
            if ($request->has($queryKey)) {
                if (test_request_int($queryKey)) {
                    $needed_volunteer_types[$needed_volunteertype_id] = trim($request->input($queryKey));
                } else {
                    $valid = false;
                    error(sprintf(
                        __('Please check your input for needed volunteers of type %s.'),
                        $volunteertypes[$needed_volunteertype_id]
                    ));
                }
            }
        }

        if ($valid) {
            $oldShift = Shift::find($shift->id);

            $shift->shift_type_id = $shifttype_id;
            $shift->title = $title;
            $shift->description = $description;
            $shift->location_id = $rid;
            $shift->start = $start;
            $shift->end = $end;
            $shift->updatedBy()->associate(auth()->user());
            $shift->save();

            event('shift.updating', [
                'shift' => $shift,
                'oldShift' => $oldShift,
            ]);

            NeededVolunteerType::whereShiftId($shift_id)->delete();
            $needed_volunteer_types_info = [];
            foreach ($needed_volunteer_types as $type_id => $count) {
                $volunteertype = VolunteerType::find($type_id);
                if (!empty($volunteertype) && $count > 0) {
                    $neededVolunteerType = new NeededVolunteerType();
                    $neededVolunteerType->shift()->associate($shift);
                    $neededVolunteerType->volunteer_type_id = $type_id;
                    $neededVolunteerType->count = $count;
                    $neededVolunteerType->save();

                    $needed_volunteer_types_info[] = $volunteertypes[$type_id] . ': ' . $count;
                }
            }

            volunteersystem_log(
                'Updated shift \'' . $shifttypes[$shifttype_id] . ', ' . $title
                . '\' from ' . $start->format('Y-m-d H:i')
                . ' to ' . $end->format('Y-m-d H:i')
                . ' with volunteer types ' . join(', ', $needed_volunteer_types_info)
                . ' and description ' . $description
            );
            success(__('Shift updated.'));

            throw_redirect(shift_link($shift));
        }
    }

    $volunteer_types_spinner = '';
    foreach ($volunteertypes as $volunteertype_id => $volunteertype_name) {
        $volunteer_types_spinner .=
            '<div class="col-sm-6 col-md-8 col-lg-6 col-xl-4 col-xxl-3">'
            . form_spinner(
                'volunteertype_count_' . $volunteertype_id,
                htmlspecialchars($volunteertype_name),
                $needed_volunteer_types[$volunteertype_id],
                [],
                (bool) ScheduleShift::whereShiftId($shift->id)->first(),
            )
            . '</div>';
    }

    $link = button(url('/shifts', ['action' => 'view', 'shift_id' => $shift_id]), icon('chevron-left'), 'btn-sm', '', __('general.back'));
    return page_with_title(
        $link . ' ' . shifts_title(),
        [
            msg(),
            '<noscript>'
            . info(__('This page is much more comfortable with javascript.'), true)
            . '</noscript>',
            form([
                div('row', [
                    div('col-md-6 col-xl-5', [
                        form_select('shifttype_id', __('Shift type'), $shifttypes, $shifttype_id),
                        form_text('title', __('title.title'), $title),
                        form_select('rid', __('Location'), $locations, $rid),
                    ]),
                    div('col-md-6 col-xl-7', [
                        form_textarea('description', __('Additional description'), $description),
                        form_info(
                            '',
                            __('This description is for single shifts, otherwise please use the description in shift type.')
                        ),
                    ]),
                ]),
                div('row', [
                    div('col-md-6 col-xl-5', [
                        div('row', [
                            div('col-lg-6', [
                                form_datetime('start', __('shifts.start'), $start),
                            ]),
                            div('col-lg-6', [
                                form_datetime('end', __('shifts.end'), $end),
                            ]),
                        ]),
                    ]),
                    div('col-md-6 col-xl-7', [
                        '<h4>' . __('Needed volunteers') . '</h4>',
                        div('row', [
                            $volunteer_types_spinner,
                        ]),
                    ]),
                ]),
                form_submit('submit', icon('save') . __('form.save')),
            ]),
        ]
    );
}

function shift_delete_controller(): void
{
    $request = request();

    // Only accessible for admins / ShiCos with user_shifts_admin privileg
    if (!auth()->can('user_shifts_admin')) {
        throw new HttpForbidden();
    }

    // Must contain shift id and confirmation
    if (!$request->has('delete_shift') || !$request->hasPostData('delete')) {
        throw new HttpNotFound();
    }

    $shift_id = $request->input('delete_shift');
    $shift = Shift::findOrFail($shift_id);

    event('shift.deleting', ['shift' => $shift]);

    $shift->delete();

    volunteersystem_log(
        'Deleted shift ' . $shift->title . ': ' . $shift->shiftType->name
        . ' from ' . $shift->start->format('Y-m-d H:i')
        . ' to ' . $shift->end->format('Y-m-d H:i')
    );
    success(__('Shift deleted.'));

    /** @var Redirector $redirect */
    $redirect = app('redirect');
    $old = $redirect->back()->getHeaderLine('location');
    if (Str::contains($old, '/shifts') && Str::contains($old, 'action=view')) {
        throw_redirect(url('/user-shifts'));
    }

    throw_redirect($old);
}

/**
 * @return array
 */
function shift_controller()
{
    $user = auth()->user();
    $request = request();

    if (!auth()->can('user_shifts')) {
        throw_redirect(url('/'));
    }

    if (!$request->has('shift_id')) {
        throw_redirect(url('/user-shifts'));
    }

    $shift = Shift::with(['shiftEntries.user.state', 'shiftEntries.volunteerType'])
        ->findOrFail($request->input('shift_id'));
    $shift = Shift($shift);
    if (empty($shift)) {
        error(__('Shift could not be found.'));
        throw_redirect(url('/user-shifts'));
    }

    $shifttype = $shift->shiftType;
    $location = $shift->location;
    /** @var VolunteerType[] $volunteertypes */
    $volunteertypes = VolunteerType::all();
    $user_shifts = Shifts_by_user($user->id);

    $shift_signup_state = new ShiftSignupState(ShiftSignupStatus::OCCUPIED, 0);
    foreach ($volunteertypes as $volunteertype) {
        $needed_volunteertype = NeededVolunteertype_by_Shift_and_Volunteertype($shift, $volunteertype);
        if (empty($needed_volunteertype)) {
            continue;
        }

        $shift_entries = $shift->shiftEntries()
            ->where('volunteer_type_id', $volunteertype->id)
            ->get();
        $needed_volunteertype = (new VolunteerType())->forceFill($needed_volunteertype);

        $volunteertype_signup_state = Shift_signup_allowed(
            $user,
            $shift,
            $volunteertype,
            null,
            $user_shifts,
            $needed_volunteertype,
            $shift_entries
        );
        $shift_signup_state->combineWith($volunteertype_signup_state);
        $volunteertype->shift_signup_state = $volunteertype_signup_state;
    }

    return [
        htmlspecialchars($shift->shiftType->name),
        Shift_view($shift, $shifttype, $location, $volunteertypes, $shift_signup_state),
    ];
}

/**
 * @return array
 */
function shifts_controller()
{
    $request = request();
    if (!$request->has('action')) {
        throw_redirect(url('/user-shifts'));
    }

    return match ($request->input('action')) {
        'view' => shift_controller(),
        'next' => shift_next_controller(), // throws redirect
        default => throw_redirect(url('/')),
    };
}

/**
 * Redirects the user to his next shift.
 */
function shift_next_controller()
{
    if (!auth()->can('user_shifts')) {
        throw_redirect(url('/'));
    }

    $upcoming_shifts = ShiftEntries_upcoming_for_user(auth()->user());

    if (!$upcoming_shifts->isEmpty()) {
        throw_redirect(shift_link($upcoming_shifts[0]->shift));
    }

    throw_redirect(url('/user-shifts'));
}
