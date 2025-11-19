<?php

use Volunteersystem\Helpers\Carbon;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

/**
 * @return string
 */
function admin_free_title()
{
    return __('Free volunteers');
}

/**
 * @return string
 */
function admin_free()
{
    $request = request();

    $search = '';
    if ($request->has('search')) {
        $search = strip_request_item('search');
    }

    /** @var VolunteerType[]|Collection $volunteer_types_source */
    $volunteer_types_source = VolunteerType::all(['id', 'name']);
    $volunteer_types = [
        '' => __('All'),
    ];
    foreach ($volunteer_types_source as $volunteer_type) {
        $volunteer_types[$volunteer_type->id] = $volunteer_type->name;
    }

    $volunteerType = $request->input('volunteertype', '');

    /** @var User[] $users */
    $users = [];
    if ($request->has('submit')) {
        $query = User::with(['personalData', 'contact', 'state', 'settings'])
            ->select('users.*')
            ->leftJoin('shift_entries', 'users.id', 'shift_entries.user_id')
            ->leftJoin('users_state', 'users.id', 'users_state.user_id')
            ->leftJoin('shifts', function ($join) {
                /** @var JoinClause $join */
                $join->on('shift_entries.shift_id', '=', 'shifts.id')
                    ->where('shifts.start', '<', Carbon::now())
                    ->where('shifts.end', '>', Carbon::now());
            })
            ->whereNotNull('users_state.arrival_date')
            ->whereNull('shifts.id')
            ->orderBy('users.name')
            ->groupBy('users.id');

        if (!empty($volunteerType)) {
            $query->join('user_volunteer_type', function ($join) use ($volunteerType) {
                /** @var JoinClause $join */
                $join->on('user_volunteer_type.user_id', '=', 'users.id')
                    ->where('user_volunteer_type.volunteer_type_id', '=', $volunteerType);
            });

            $query->join('volunteer_types', function ($join) {
                /** @var JoinClause $join */
                $join->on('user_volunteer_type.volunteer_type_id', '=', 'volunteer_types.id')
                    ->whereNotNull('user_volunteer_type.confirm_user_id')
                    ->orWhere('volunteer_types.restricted', '=', '0');
            });
        }

        $users = $query->get();
    }

    $free_users_table = [];
    if ($search == '') {
        $tokens = [];
    } else {
        $tokens = explode(' ', $search);
    }
    foreach ($users as $usr) {
        if (count($tokens) > 0) {
            $match = false;
            $index = join('', $usr->attributesToArray());
            foreach ($tokens as $token) {
                $token = trim($token);
                if (!empty($token) && stristr($index, $token)) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
        }

        $email = $usr->contact->email ?: $usr->email;
        $free_users_table[] = [
            'name'        => User_Nick_render($usr)
                . User_Pronoun_render($usr)
                . user_info_icon($usr),
            'shift_state' => User_shift_state_render($usr),
            'last_shift'  => User_last_shift_render($usr),
            'dect'        => sprintf('<a href="tel:%s">%1$s</a>', htmlspecialchars((string) $usr->contact->dect)),
            'email'       => $usr->settings->email_human
                ? sprintf('<a href="mailto:%s">%1$s</a>', htmlspecialchars((string) $email))
                : icon('eye-slash'),
            'actions'     =>
                auth()->can('admin_user')
                    ? button(url('/admin-user', ['id' => $usr->id]), icon('pencil'), 'btn-sm', '', __('form.edit'))
                    : '',
        ];
    }
    return page_with_title(admin_free_title(), [
        form([
            div('row', [
                div('col-md-12 form-inline', [
                    div('row', [
                        form_text('search', __('form.search'), $search, null, null, null, 'col'),
                        form_select('volunteertype', __('Volunteer type'), $volunteer_types, $volunteerType, '', 'col'),
                        form_submit('submit', icon('search') . __('form.search')),
                    ]),
                ]),
            ]),
        ]),
        table([
            'name'        => __('general.name'),
            'shift_state' => __('shift.next'),
            'last_shift'  => __('Last shift'),
            'dect'        => __('general.dect'),
            'email'       => __('general.email'),
            'actions'     => '',
        ], $free_users_table),
    ]);
}
