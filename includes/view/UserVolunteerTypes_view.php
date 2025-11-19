<?php

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;

/**
 * @param UserVolunteerType $user_volunteertype
 * @param User          $user
 * @param VolunteerType     $volunteertype
 * @param bool          $supporter
 * @return string
 */
function UserVolunteerType_update_view(UserVolunteerType $user_volunteertype, User $user, VolunteerType $volunteertype, bool $supporter)
{
    return page_with_title($supporter ? __('Add supporter rights') : __('Remove supporter rights'), [
        msg(),
        info(sprintf(
            $supporter
                ? __('Do you really want to add supporter rights for %s to %s?')
                : __('Do you really want to remove supporter rights for %s from %s?'),
            $volunteertype->name,
            $user->displayName
        ), true),
        form([
            buttons([
                button(
                    url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]),
                    icon('x-lg') . __('form.cancel')
                ),
                form_submit('submit', icon('check-lg') . __('Yes'), 'btn-primary', false),
            ]),
        ], url('/user-volunteertypes', [
            'action'            => 'update',
            'user_volunteertype_id' => $user_volunteertype->id,
            'supporter'         => ($supporter ? '1' : '0'),
        ])),
    ]);
}

/**
 * @param VolunteerType $volunteertype
 * @return string
 */
function UserVolunteerTypes_delete_all_view(VolunteerType $volunteertype)
{
    return page_with_title(__('Deny all users'), [
        msg(),
        info(sprintf(__('Do you really want to deny all users for %s?'), $volunteertype->name), true),
        form([
            buttons([
                button(
                    url(
                        '/volunteertypes',
                        ['action' => 'view', 'volunteertype_id' => $volunteertype->id]
                    ),
                    icon('x-lg') . __('form.cancel')
                ),
                form_submit('deny_all', icon('check-lg') . __('Yes'), 'btn-primary', false),
            ]),
        ], url('/user-volunteertypes', ['action' => 'delete_all', 'volunteertype_id' => $volunteertype->id])),
    ]);
}

/**
 * @param VolunteerType $volunteertype
 * @return string
 */
function UserVolunteerTypes_confirm_all_view(VolunteerType $volunteertype)
{
    return page_with_title(__('Confirm all users'), [
        msg(),
        info(sprintf(__('Do you really want to confirm all users for %s?'), $volunteertype->name), true),
        form([
            buttons([
                button(volunteertype_link($volunteertype->id), icon('x-lg') . __('form.cancel')),
                form_submit('confirm_all', icon('check-lg') . __('Yes'), 'btn-primary', false),
            ]),
        ], url('/user-volunteertypes', ['action' => 'confirm_all', 'volunteertype_id' => $volunteertype->id])),
    ]);
}

/**
 * @param UserVolunteerType $user_volunteertype
 * @param User          $user
 * @param VolunteerType     $volunteertype
 * @return string
 */
function UserVolunteerType_confirm_view(UserVolunteerType $user_volunteertype, User $user, VolunteerType $volunteertype)
{
    return page_with_title(__('Confirm volunteer type for user'), [
        msg(),
        info(sprintf(
            __('Do you really want to confirm %s for %s?'),
            $user->displayName,
            $volunteertype->name
        ), true),
        form([
            buttons([
                button(volunteertype_link($volunteertype->id), icon('x-lg') . __('form.cancel')),
                form_submit('confirm_user', icon('check-lg') . __('Yes'), 'btn-primary', false),
            ]),
        ], url('/user-volunteertypes', ['action' => 'confirm', 'user_volunteertype_id' => $user_volunteertype->id])),
    ]);
}

/**
 * @param UserVolunteerType $user_volunteertype
 * @param User          $user
 * @param VolunteerType     $volunteertype
 * @param bool          $isOwnVolunteerType
 * @return string
 */
function UserVolunteerType_delete_view(UserVolunteerType $user_volunteertype, User $user, VolunteerType $volunteertype, bool $isOwnVolunteerType)
{
    return page_with_title(__('Leave volunteer type'), [
        msg(),
        info(sprintf(
            $isOwnVolunteerType ? __('Do you really want to leave "%2$s"?') : __('Do you really want to remove "%s" from "%s"?'),
            $user->displayName,
            $volunteertype->name
        ), true),
        form([
            buttons([
                button(volunteertype_link($volunteertype->id), icon('x-lg') . __('form.cancel')),
                form_submit('delete', icon('check-lg') . __('Yes'), 'btn-primary', false),
            ]),
        ], url('/user-volunteertypes', ['action' => 'delete', 'user_volunteertype_id' => $user_volunteertype->id])),
    ], true);
}

/**
 * @param VolunteerType $volunteertype
 * @param array     $users_select
 * @param int       $user_id
 * @return string
 */
function UserVolunteerType_add_view(VolunteerType $volunteertype, $users_select, $user_id)
{
    $link = button(
        url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id]),
        icon('chevron-left'),
        'btn-sm',
        '',
        __('general.back')
    );
    return page_with_title($link . ' ' . __('Add user to volunteer type'), [
        msg(),
        form([
            form_info(__('Volunteer type'), htmlspecialchars($volunteertype->name)),
            $volunteertype->restricted
                ? form_checkbox('auto_confirm_user', __('Confirm user'), true)
                : '',
            auth()->can('admin_volunteer_types') || config('supporters_can_promote')
                ? form_checkbox('set_supporter', __('Supporter'), false)
                : '',
            form_select('user_id', __('general.user'), $users_select, $user_id, '', '', 'user_volunteer_type_add_user_id'),
            form_submit('submit', icon('plus-lg') . __('general.add')),
        ]),
    ]);
}

/**
 * @param User      $user
 * @param VolunteerType $volunteertype
 * @return string
 */
function UserVolunteerType_join_view($user, VolunteerType $volunteertype)
{
    $isOther = $user->id != auth()->user()->id;
    return page_with_title(sprintf(__('Join %s'), htmlspecialchars($volunteertype->name)), [
        msg(),
        info(sprintf(
            $isOther ? __('Do you really want to add %s to %s?') : __('Do you want to join %2$s?'),
            $user->displayName,
            $volunteertype->name
        ), true),
        form([
            auth()->can('admin_user_volunteertypes') ? form_checkbox('auto_confirm_user', __('Confirm user'), true) : '',
            buttons([
                button(volunteertype_link($volunteertype->id), icon('x-lg') . __('form.cancel')),
                form_submit('submit', icon('save') . __('form.save'), 'btn-primary', false),
            ]),
        ], url(
            '/user-volunteertypes',
            ['action' => 'add', 'volunteertype_id' => $volunteertype->id, 'user_id' => $user->id]
        )),
    ]);
}
