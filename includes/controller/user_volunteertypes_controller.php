<?php

use Volunteersystem\Http\Exceptions\HttpForbidden;
use Volunteersystem\Http\Exceptions\HttpNotFound;
use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Display a hint for team/volunteertype supporters if there are unconfirmed users for his volunteertype.
 *
 * @return string|null
 */
function user_volunteertypes_unconfirmed_hint()
{
    $restrictedSupportedVolunteerTypes = auth()
        ->user()
        ->userVolunteerTypes()
        ->wherePivot('supporter', true)
        ->where('restricted', true)
        ->get();

    /** @var Collection|UserVolunteerType[] $unconfirmed_user_volunteertypes */
    $unconfirmed_user_volunteertypes = UserVolunteerType::query()
        ->with('VolunteerType')
        ->select(['user_volunteer_type.*', UserVolunteerType::query()->raw('count(volunteer_type_id) as users_count')])
        ->whereIn('volunteer_type_id', $restrictedSupportedVolunteerTypes->pluck('id')->toArray())
        ->whereNull('confirm_user_id')
        ->groupBy('volunteer_type_id')
        ->get();

    if (!$unconfirmed_user_volunteertypes->count()) {
        return null;
    }

    $unconfirmed_links = [];
    foreach ($unconfirmed_user_volunteertypes as $user_volunteertype) {
        $unconfirmed_links[] = '<a href="'
            . url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $user_volunteertype->volunteer_type_id])
            . '">' . htmlspecialchars($user_volunteertype->volunteerType->name)
            . ' (+' . $user_volunteertype->users_count . ')'
            . '</a>';
    }

    $count = $unconfirmed_user_volunteertypes->count();
    return
        _e(
            'There are unconfirmed volunteers in %d volunteer type. Volunteer type that needs approval:',
            'There are unconfirmed volunteers in %d volunteer types. Volunteer types that need approvals:',
            $count,
            [$count]
        )
        . ' ' . join(', ', $unconfirmed_links);
}

/**
 * Remove all unconfirmed users from a specific volunteertype.
 *
 * @return array
 */
function user_volunteertypes_delete_all_controller(): array
{
    $request = request();

    if (!$request->has('volunteertype_id')) {
        error(__('Volunteertype doesn\'t exist.'));
        throw_redirect(url('/volunteertypes'));
    }

    $volunteertype = VolunteerType::findOrFail($request->input('volunteertype_id'));
    if (!auth()->user()->isVolunteerTypeSupporter($volunteertype) && !auth()->can('admin_user_volunteertypes')) {
        error(__('You are not allowed to delete all users for this volunteer type.'));
        throw_redirect(url('/volunteertypes'));
    }

    if ($request->hasPostData('deny_all')) {
        UserVolunteerType::whereVolunteerTypeId($volunteertype->id)
            ->whereNull('confirm_user_id')
            ->delete();

        volunteersystem_log(sprintf('Denied all users for volunteer type %s', VolunteerType_name_render($volunteertype, true)));
        success(sprintf(__('Denied all users for volunteer type %s.'), $volunteertype->name));
        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        __('Deny all users'),
        UserVolunteerTypes_delete_all_view($volunteertype),
    ];
}

/**
 * Confirm all unconfirmed users for an volunteertype.
 *
 * @return array
 */
function user_volunteertypes_confirm_all_controller(): array
{
    $user = auth()->user();
    $request = request();

    if (!$request->has('volunteertype_id')) {
        error(__('Volunteertype doesn\'t exist.'));
        throw_redirect(url('/volunteertypes'));
    }

    $volunteertype = VolunteerType::findOrFail($request->input('volunteertype_id'));
    if (!auth()->can('admin_user_volunteertypes') && !$user->isVolunteerTypeSupporter($volunteertype)) {
        error(__('You are not allowed to confirm all users for this volunteer type.'));
        throw_redirect(url('/volunteertypes'));
    }

    if ($request->hasPostData('confirm_all')) {
        /** @var Collection|User[] $users */
        $users = $volunteertype->userVolunteerTypes()->wherePivot('confirm_user_id', '=', null)->get();
        UserVolunteerType::whereVolunteerTypeId($volunteertype->id)
            ->whereNull('confirm_user_id')
            ->update(['confirm_user_id' => $user->id]);

        volunteersystem_log(sprintf('Confirmed all users for volunteer type %s', VolunteerType_name_render($volunteertype, true)));
        success(sprintf(__('Confirmed all users for volunteer type %s.'), $volunteertype->name));

        foreach ($users as $user) {
            user_volunteertype_confirm_email($user, $volunteertype);
        }

        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        __('Confirm all users'),
        UserVolunteerTypes_confirm_all_view($volunteertype),
    ];
}

/**
 * Confirm a user for an volunteertype.
 *
 * @return array
 */
function user_volunteertype_confirm_controller(): array
{
    $user = auth()->user();
    $request = request();

    if (!$request->has('user_volunteertype_id')) {
        throw new HttpNotFound();
    }

    /** @var UserVolunteerType $user_volunteertype */
    $user_volunteertype = UserVolunteerType::findOrFail($request->input('user_volunteertype_id'));
    $volunteertype = $user_volunteertype->volunteerType;
    if (!$user->isVolunteerTypeSupporter($volunteertype) && !auth()->can('admin_user_volunteertypes')) {
        error(__('You are not allowed to confirm this users volunteer type.'));
        throw_redirect(url('/volunteertypes'));
    }

    $user_source = $user_volunteertype->user;
    if ($request->hasPostData('confirm_user')) {
        $user_volunteertype->confirmUser()->associate($user);
        $user_volunteertype->save();

        volunteersystem_log(sprintf(
            '%s confirmed for volunteer type %s',
            User_Nick_render($user_source, true),
            VolunteerType_name_render($volunteertype, true)
        ));
        success(sprintf(__('%s confirmed for volunteer type %s.'), $user_source->displayName, $volunteertype->name));

        user_volunteertype_confirm_email($user_source, $volunteertype);

        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        __('Confirm volunteer type for user'),
        UserVolunteerType_confirm_view($user_volunteertype, $user_source, $volunteertype),
    ];
}

function user_volunteertype_confirm_email(User $user, VolunteerType $volunteertype): void
{
    if (!$user->settings->email_shiftinfo) {
        return;
    }

    /** @var VolunteersystemMailer $mailer */
    $mailer = app(VolunteersystemMailer::class);
    $mailer->sendViewTranslated(
        $user,
        'notification.volunteertype.confirmed',
        'emails/volunteertype-confirmed',
        ['name' => $volunteertype->name, 'volunteertype' => $volunteertype, 'username' => $user->displayName]
    );
}

function user_volunteertype_add_email(User $user, VolunteerType $volunteertype): void
{
    if (!$user->settings->email_shiftinfo || $user->id == auth()->user()->id) {
        return;
    }

    /** @var VolunteersystemMailer $mailer */
    $mailer = app(VolunteersystemMailer::class);
    $mailer->sendViewTranslated(
        $user,
        'notification.volunteertype.added',
        'emails/volunteertype-added',
        ['name' => $volunteertype->name, 'volunteertype' => $volunteertype, 'username' => $user->displayName]
    );
}

/**
 * Remove a user from an Volunteertype.
 *
 * @return array
 */
function user_volunteertype_delete_controller(): array
{
    $request = request();
    $user = auth()->user();

    if (!$request->has('user_volunteertype_id')) {
        throw new HttpNotFound();
    }

    /** @var UserVolunteerType $user_volunteertype */
    $user_volunteertype = UserVolunteerType::findOrFail($request->input('user_volunteertype_id'));
    $volunteertype = $user_volunteertype->volunteerType;
    $user_source = $user_volunteertype->user;
    $isOwnVolunteerType = $user->id == $user_source->id;
    if (
        !$isOwnVolunteerType
        && !$user->isVolunteerTypeSupporter($volunteertype)
        && !auth()->can('admin_user_volunteertypes')
    ) {
        error(__('You are not allowed to delete this users volunteer type.'));
        throw_redirect(url('/volunteertypes'));
    }

    if ($request->hasPostData('delete')) {
        $user_volunteertype->delete();

        volunteersystem_log(sprintf('User "%s" removed from "%s".', User_Nick_render($user_source, true), $volunteertype->name));
        success(sprintf($isOwnVolunteerType ? __('You successfully left "%2$s".') : __('User "%s" removed from "%s".'), $user_source->displayName, $volunteertype->name));

        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        __('Leave volunteer type'),
        UserVolunteerType_delete_view($user_volunteertype, $user_source, $volunteertype, $isOwnVolunteerType),
    ];
}

/**
 * Update an UserVolunteerType.
 *
 * @return array
 */
function user_volunteertype_update_controller(): array
{
    $supporter = false;
    $request = request();

    if (!$request->has('user_volunteertype_id')) {
        throw new HttpNotFound();
    }
    if (!auth()->can('admin_volunteer_types') && !config('supporters_can_promote')) {
        throw new HttpForbidden();
    }

    if ($request->has('supporter') && preg_match('/^[01]$/', $request->input('supporter'))) {
        $supporter = $request->input('supporter') == '1';
    } else {
        error(__('No supporter update given.'));
        throw_redirect(url('/volunteertypes'));
    }

    /** @var UserVolunteerType $user_volunteertype */
    $user_volunteertype = UserVolunteerType::findOrFail($request->input('user_volunteertype_id'));
    $volunteertype = $user_volunteertype->volunteerType;
    $user_source = $user_volunteertype->user;

    if ($request->hasPostData('submit')) {
        $user_volunteertype->supporter = $supporter;
        $user_volunteertype->save();

        $msg = $supporter
            ? __('Added supporter rights for %s to %s.')
            : __('Removed supporter rights for %s from %s.');
        volunteersystem_log(sprintf(
            $msg,
            VolunteerType_name_render($volunteertype, true),
            User_Nick_render($user_source, true)
        ));
        success(sprintf($msg, $volunteertype->name, $user_source->displayName));

        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        $supporter ? __('Add supporter rights') : __('Remove supporter rights'),
        UserVolunteerType_update_view($user_volunteertype, $user_source, $volunteertype, $supporter),
    ];
}

/**
 * User joining an Volunteertype (Or supporter doing this for him).
 *
 * @return array
 */
function user_volunteertype_add_controller(): array
{
    /** @var VolunteerType $volunteertype */
    $volunteertype = VolunteerType::findOrFail(request()->input('volunteertype_id'));

    // User is joining by itself
    if (!auth()->user()->isVolunteerTypeSupporter($volunteertype) && !auth()->can('admin_user_volunteertypes')) {
        return user_volunteertype_join_controller($volunteertype);
    }

    // Allow to add any user

    // Default selection
    $user_source = auth()->user();

    // Load all users with userVolunteerTypes
    /** @var Collection|User[] $users */
    $users = User::with('userVolunteerTypes')->orderBy('name')->get();

    // Add membership state to displayname
    $users_select = [];
    foreach ($users as $user) {
        $name = $user->displayName;
        /** @var VolunteerType|null $userVolunteerType */
        $userVolunteerType = $user->userVolunteerTypes->where('id', $volunteertype->id)->first();
        if ($userVolunteerType) {
            $membershipState = __('Member');
            if ($userVolunteerType->pivot->supporter) {
                $membershipState = __('Supporter');
            } elseif (
                !$userVolunteerType->pivot->isConfirmed
            ) {
                $membershipState = __('Unconfirmed');
            }
            $name = __('%s (%s)', [$name, $membershipState]);
        }
        $users_select[$user->id] = $name;
    }

    $request = request();
    if ($request->hasPostData('submit')) {
        $user_source = load_user();

        if (
            !$volunteertype
                ->userVolunteerTypes()
                ->wherePivot('user_id', $user_source->id)
                ->wherePivotNotNull('confirm_user_id')
                ->exists()
        ) {
            /** @var UserVolunteerType $userVolunteerType */
            $userVolunteerType = UserVolunteerType::firstOrCreate([
                'user_id' => $user_source->id,
                'volunteer_type_id' => $volunteertype->id,
            ]);

            $setSupporter = $request->hasPostData('set_supporter')
                && (auth()->can('admin_volunteer_types') || config('supporters_can_promote'));
            if ($setSupporter) {
                $userVolunteerType->supporter = true;
            }
            if ($request->hasPostData('auto_confirm_user') || $setSupporter) {
                $userVolunteerType->confirmUser()->associate($user_source);
            }
            $userVolunteerType->save();

            volunteersystem_log(sprintf(
                'User %s added to volunteer type %s, confirmed: %s, supporter: %s.',
                User_Nick_render($user_source, true),
                VolunteerType_name_render($volunteertype, true),
                $userVolunteerType->confirm_user_id ? 'true' : 'false',
                $userVolunteerType->supporter ? 'true' : 'false',
            ));

            success(sprintf(__('User %s added to %s.'), $user_source->displayName, $volunteertype->name));
            user_volunteertype_add_email($user_source, $volunteertype);

            throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
        }
    }

    return [
        __('Add user to volunteer type'),
        UserVolunteerType_add_view($volunteertype, $users_select, $user_source->id),
    ];
}

/**
 * A user joins an volunteertype.
 *
 * @param VolunteerType $volunteertype
 * @return array
 */
function user_volunteertype_join_controller(VolunteerType $volunteertype)
{
    $user = auth()->user();

    /** @var UserVolunteerType $user_volunteertype */
    $user_volunteertype = UserVolunteerType::whereUserId($user->id)->where('volunteer_type_id', $volunteertype->id)->first();
    if (!empty($user_volunteertype)) {
        error(sprintf(__('You are already a %s.'), $volunteertype->name));
        throw_redirect(url('/volunteertypes'));
    }

    $request = request();
    if ($request->hasPostData('submit')) {
        $userVolunteerType = new UserVolunteerType();
        $userVolunteerType->user()->associate($user);
        $userVolunteerType->volunteerType()->associate($volunteertype);
        $userVolunteerType->save();

        volunteersystem_log(sprintf(
            'User %s joined %s.',
            User_Nick_render($user, true),
            VolunteerType_name_render($volunteertype, true)
        ));
        success(sprintf(__('You joined %s.'), $volunteertype->name));

        if (auth()->can('admin_user_volunteertypes') && $request->hasPostData('auto_confirm_user')) {
            $userVolunteerType->confirmUser()->associate($user);
            $userVolunteerType->save();

            volunteersystem_log(sprintf(
                'User %s confirmed as %s.',
                User_Nick_render($user, true),
                VolunteerType_name_render($volunteertype, true)
            ));
        }

        throw_redirect(url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]));
    }

    return [
        sprintf(__('Join %s'), htmlspecialchars($volunteertype->name)),
        UserVolunteerType_join_view($user, $volunteertype),
    ];
}

/**
 * Route UserVolunteerType actions.
 *
 * @return array
 */
function user_volunteertypes_controller(): array
{
    $request = request();
    if (!$request->has('action')) {
        throw_redirect(url('/volunteertypes'));
    }

    return match ($request->input('action')) {
        'delete_all'  => user_volunteertypes_delete_all_controller(),
        'confirm_all' => user_volunteertypes_confirm_all_controller(),
        'confirm'     => user_volunteertype_confirm_controller(),
        'delete'      => user_volunteertype_delete_controller(),
        'update'      => user_volunteertype_update_controller(),
        'add'         => user_volunteertype_add_controller(),
        default       => throw_redirect(url('/volunteertyps')),
    };
}
