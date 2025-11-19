<?php

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftSignupStatus;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\ShiftSignupState;
use Illuminate\Database\Eloquent\Collection;

/**
 * Route shift entry actions.
 *
 * @return array
 */
function shift_entries_controller(): array
{
    $user = auth()->user();
    if (!$user) {
        throw_redirect(url('/login'));
    }

    $action = strip_request_item('action');
    if (empty($action)) {
        throw_redirect(user_link($user->id));
    }

    return match ($action) {
        'create' => shift_entry_create_controller(),
        'delete' => shift_entry_delete_controller(),
        default => ['', ''],
    };
}

/**
 * Sign up for a shift.
 *
 * @return array
 */
function shift_entry_create_controller(): array
{
    $user = auth()->user();
    $request = request();

    if ($user->isFreeloader()) {
        throw_redirect(url('/user_myshifts'));
    }

    $shift = Shift($request->input('shift_id'));
    if (empty($shift)) {
        throw_redirect(user_link($user->id));
    }

    $volunteertype = VolunteerType::find($request->input('volunteertype_id'));

    if (auth()->can('user_shifts_admin')) {
        return shift_entry_create_controller_admin($shift, $volunteertype);
    }

    if (empty($volunteertype)) {
        throw_redirect(user_link($user->id));
    }

    if ($user->isVolunteerTypeSupporter($volunteertype) || auth()->can('admin_user_volunteertypes')) {
        return shift_entry_create_controller_supporter($shift, $volunteertype);
    }

    return shift_entry_create_controller_user($shift, $volunteertype);
}

/**
 * Sign up for a shift.
 * Case: Admin
 *
 * @param Shift          $shift
 * @param VolunteerType|null $volunteertype
 * @return array
 */
function shift_entry_create_controller_admin(Shift $shift, ?VolunteerType $volunteertype): array
{
    $signup_user = auth()->user();
    $request = request();

    if ($request->has('user_id')) {
        $signup_user = User::find($request->input('user_id'));
    }
    if (!$signup_user) {
        throw_redirect(shift_link($shift));
    }

    $volunteertypes = VolunteerType::all();
    if ($request->hasPostData('volunteertype_id')) {
        $volunteertype = VolunteerType::find($request->postData('volunteertype_id'));
    }
    if (empty($volunteertype)) {
        if (count($volunteertypes) == 0) {
            throw_redirect(shift_link($shift));
        }
        $volunteertype = $volunteertypes[0];
    }

    if ($request->hasPostData('submit')) {
        $shiftEntry = new ShiftEntry();
        $shiftEntry->shift()->associate($shift);
        $shiftEntry->volunteerType()->associate($volunteertype);
        $shiftEntry->user()->associate($signup_user);
        $shiftEntry->save();
        ShiftEntry_onCreate($shiftEntry);

        success(sprintf(__('%s has been subscribed to the shift.'), $signup_user->displayName));
        throw_redirect(shift_link($shift));
    }

    /** @var User[]|Collection $users */
    $users = User::with(['userVolunteerTypes', 'shiftEntries'])->orderBy('name')->get();
    $users_select = [];
    foreach ($users as $user) {
        $name = $user->displayName;
        if ($user->userVolunteerTypes->where('id', $volunteertype->id)->isEmpty()) {
            $name = __('%s (not "%s")', [$name, $volunteertype->name]);
        }
        if ($user->shiftEntries->where('shift_id', $shift->id)->isNotEmpty()) {
            $name = __('%s (already in shift)', [$name]);
        }
        $users_select[$user->id] = $name;
    }

    $volunteertypes_select = $volunteertypes->pluck('name', 'id')->toArray();
    $location = $shift->location;
    return [
        ShiftEntry_create_title(),
        ShiftEntry_create_view_admin($shift, $location, $volunteertype, $volunteertypes_select, $signup_user, $users_select),
    ];
}

/**
 * Sign up for a shift.
 * Case: Supporter
 *
 * @param Shift     $shift
 * @param VolunteerType $volunteertype
 * @return array
 */
function shift_entry_create_controller_supporter(Shift $shift, VolunteerType $volunteertype): array
{
    $request = request();
    $signup_user = auth()->user();

    if ($request->has('user_id')) {
        $signup_user = User::find($request->input('user_id'));
    }

    if (!$signup_user->userVolunteerTypes()->wherePivot('volunteer_type_id', $volunteertype->id)->exists()) {
        error(__('User is not in volunteer type.'));
        throw_redirect(shift_link($shift));
    }

    if ($request->hasPostData('submit')) {
        $shiftEntry = new ShiftEntry();
        $shiftEntry->shift()->associate($shift);
        $shiftEntry->volunteerType()->associate($volunteertype);
        $shiftEntry->user()->associate($signup_user);
        $shiftEntry->save();
        ShiftEntry_onCreate($shiftEntry);

        success(sprintf(__('%s has been subscribed to the shift.'), $signup_user->displayName));
        throw_redirect(shift_link($shift));
    }

    $users = $volunteertype->userVolunteerTypes->sortBy('name');
    $users_select = [];
    foreach ($users as $u) {
        $users_select[$u->id] = $u->displayName;
    }

    $location = $shift->location;
    return [
        ShiftEntry_create_title(),
        ShiftEntry_create_view_supporter($shift, $location, $volunteertype, $signup_user, $users_select),
    ];
}

/**
 * Generates an error message for the given shift signup state.
 *
 * @param ShiftSignupState $shift_signup_state
 */
function shift_entry_error_message(ShiftSignupState $shift_signup_state)
{
    match ($shift_signup_state->getState()) {
        ShiftSignupStatus::VOLUNTEERTYPE   => error(__('You need be accepted member of the volunteer type.')),
        ShiftSignupStatus::COLLIDES    => error(__('This shift collides with one of your shifts.')),
        ShiftSignupStatus::OCCUPIED    => error(__('This shift is already occupied.')),
        ShiftSignupStatus::SHIFT_ENDED => error(__('This shift ended already.')),
        ShiftSignupStatus::NOT_ARRIVED => error(__('You are not marked as arrived.')),
        ShiftSignupStatus::NOT_YET     => error(__('You are not allowed to sign up yet.')),
        ShiftSignupStatus::SIGNED_UP   => error(__('You are signed up for this shift.')),
        default => null, // ShiftSignupStatus::FREE|ShiftSignupStatus::ADMIN
    };
}

/**
 * Sign up for a shift.
 * Case: User
 *
 * @param Shift     $shift
 * @param VolunteerType $volunteertype
 * @return array
 */
function shift_entry_create_controller_user(Shift $shift, VolunteerType $volunteertype): array
{
    $request = request();

    $signup_user = auth()->user();
    $needed_volunteertype = (new VolunteerType())->forceFill(NeededVolunteertype_by_Shift_and_Volunteertype($shift, $volunteertype) ?: []);
    $shift_entries = $shift->shiftEntries()
        ->where('volunteer_type_id', $volunteertype->id)
        ->get();
    $shift_signup_state = Shift_signup_allowed(
        $signup_user,
        $shift,
        $volunteertype,
        null,
        null,
        $needed_volunteertype,
        $shift_entries
    );
    if (!$shift_signup_state->isSignupAllowed()) {
        shift_entry_error_message($shift_signup_state);
        throw_redirect(shift_link($shift));
    }

    $comment = '';
    if ($request->hasPostData('submit')) {
        $comment = strip_request_item_nl('comment');

        $shiftEntry = new ShiftEntry();
        $shiftEntry->shift()->associate($shift);
        $shiftEntry->volunteerType()->associate($volunteertype);
        $shiftEntry->user()->associate($signup_user);
        $shiftEntry->user_comment = $comment;
        $shiftEntry->save();
        ShiftEntry_onCreate($shiftEntry);

        if (
            !$volunteertype->restricted
            && !$volunteertype->userVolunteerTypes()->wherePivot('user_id', $signup_user->id)->exists()
        ) {
            $userVolunteerType = new UserVolunteerType();
            $userVolunteerType->user()->associate($signup_user);
            $userVolunteerType->volunteerType()->associate($volunteertype);
            $userVolunteerType->save();
        }

        success(__('You are subscribed. Thank you!'));
        throw_redirect(shift_link($shift));
    }

    $location = $shift->location;
    return [
        ShiftEntry_create_title(),
        ShiftEntry_create_view_user($shift, $location, $volunteertype, $comment),
    ];
}

/**
 * Link to create a shift entry.
 *
 * @param Shift     $shift
 * @param VolunteerType $volunteertype
 * @param array     $params
 * @return string URL
 */
function shift_entry_create_link(Shift $shift, VolunteerType $volunteertype, $params = [])
{
    $params = array_merge([
        'action'       => 'create',
        'shift_id'     => $shift->id,
        'volunteertype_id' => $volunteertype->id,
    ], $params);
    return url('/shift-entries', $params);
}

/**
 * Link to create a shift entry as admin.
 *
 * @param Shift $shift
 * @param array $params
 * @return string URL
 */
function shift_entry_create_link_admin(Shift $shift, $params = [])
{
    $params = array_merge([
        'action'   => 'create',
        'shift_id' => $shift->id,
    ], $params);
    return url('/shift-entries', $params);
}

/**
 * Load a shift entry from get parameter shift_entry_id.
 *
 * @return ShiftEntry
 */
function shift_entry_load()
{
    $request = request();

    if (!$request->has('shift_entry_id') || !test_request_int('shift_entry_id')) {
        throw_redirect(url('/user-shifts'));
    }

    return ShiftEntry::findOrFail($request->input('shift_entry_id'));
}

/**
 * Remove somebody from a shift.
 *
 * @return array
 */
function shift_entry_delete_controller()
{
    $user = auth()->user();
    $request = request();
    $shiftEntry = shift_entry_load();

    $shift = Shift($shiftEntry->shift);
    $volunteertype = $shiftEntry->volunteerType;
    $signout_user = $shiftEntry->user;
    if (!Shift_signout_allowed($shift, $volunteertype, $signout_user->id)) {
        error(__(
            'You are not allowed to remove this shift entry. If necessary, ask your supporter or heaven to do so.'
        ));
        throw_redirect(user_link($signout_user->id));
    }

    if ($request->hasPostData('delete')) {
        $shiftEntry->delete();
        ShiftEntry_onDelete($shiftEntry);
        success(__('Shift entry removed.'));
        throw_redirect(shift_link($shift));
    }

    if ($user->id == $signout_user->id) {
        return [
            ShiftEntry_delete_title(),
            ShiftEntry_delete_view($shift, $volunteertype, $signout_user),
        ];
    }

    return [
        ShiftEntry_delete_title(),
        ShiftEntry_delete_view_admin($shift, $volunteertype, $signout_user),
    ];
}

/**
 * Link to delete a shift entry.
 *
 * @param Shift|ShiftEntry $shiftEntry
 * @param array            $params
 * @return string URL
 */
function shift_entry_delete_link($shiftEntry, $params = [])
{
    $params = array_merge([
        'action'         => 'delete',
        'shift_entry_id' => $shiftEntry['shift_entry_id'] ?? $shiftEntry['id'],
    ], $params);
    return url('/shift-entries', $params);
}
