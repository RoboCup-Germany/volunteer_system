<?php

use Volunteersystem\Config\GoodieType;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\License;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\ShiftCalendarRenderer;
use Volunteersystem\ShiftsFilterRenderer;
use Illuminate\Support\Collection;

/**
 * VolunteerTypes
 */

/**
 * Renders the volunteertypes name as link.
 *
 * @param VolunteerType $volunteertype
 * @param bool $plain
 * @return string
 */
function VolunteerType_name_render(VolunteerType $volunteertype, $plain = false)
{
    if ($plain) {
        return sprintf('%s (%u)', $volunteertype->name, $volunteertype->id);
    }

    return '<a href="' . volunteertype_link($volunteertype->id) . '">'
        . ($volunteertype->restricted ? icon('mortarboard-fill') : '') . htmlspecialchars($volunteertype->name)
        . '</a>';
}

/**
 * Render volunteertype membership state
 *
 * @param VolunteerType $user_volunteertype UserVolunteerType and VolunteerType
 * @return string
 */
function VolunteerType_render_membership(VolunteerType $user_volunteertype)
{
    if (!empty($user_volunteertype->user_volunteer_type_id)) {
        if ($user_volunteertype->restricted) {
            if (empty($user_volunteertype->confirm_user_id)) {
                return icon('mortarboard-fill') . __('Unconfirmed');
            } elseif ($user_volunteertype->supporter) {
                return icon_bool(true) . __('Supporter');
            }
            return icon_bool(true) . __('Member');
        } elseif ($user_volunteertype->supporter) {
            return icon_bool(true) . __('Supporter');
        }
        return icon_bool(true) . __('Member');
    }
    return icon_bool(false);
}

/**
 * @param VolunteerType $volunteertype
 * @return string
 */
function VolunteerType_delete_view(VolunteerType $volunteertype)
{
    $link = button($volunteertype->id
        ? url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id])
        : url('/volunteertypes'), icon('chevron-left'), 'btn-sm', '', __('general.back'));
    return page_with_title($link . ' ' . sprintf(__('Delete volunteer type %s'), htmlspecialchars($volunteertype->name)), [
        info(sprintf(__('Do you want to delete volunteer type %s?'), $volunteertype->name), true),
        form([
            buttons([
                button(url('/volunteertypes'), icon('x-lg') . __('form.cancel')),
                form_submit('delete', icon('trash'), 'btn-danger', false, 'primary', __('form.delete')),
            ]),
        ]),
    ], true);
}

/**
 * Render volunteertype edit form.
 *
 * @param VolunteerType $volunteertype The volunteertype to edit
 * @param boolean $supporter_mode Is the user a supporter of this volunteertype?
 * @return string
 */
function VolunteerType_edit_view(VolunteerType $volunteertype, bool $supporter_mode)
{
    $requires_ifsg = '';
    $requires_driving_license = '';
    if (config('ifsg_enabled')) {
        $requires_ifsg = $supporter_mode ?
            form_info(
                __('volunteertype.ifsg.required'),
                $volunteertype->requires_ifsg_certificate
                    ? __('Yes')
                    : __('No')
            ) : form_checkbox(
                'requires_ifsg_certificate',
                __('volunteertype.ifsg.required'),
                $volunteertype->requires_ifsg_certificate
            );
    }
    if (config('driving_license_enabled')) {
        $requires_driving_license = $supporter_mode ?
            form_info(
                __('Requires driver license'),
                $volunteertype->requires_driver_license
                    ? __('Yes')
                    : __('No')
            ) : form_checkbox(
                'requires_driver_license',
                __('Requires driver license'),
                $volunteertype->requires_driver_license
            );
    }

    $link = button($volunteertype->id
        ? url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $volunteertype->id])
        : url('/volunteertypes'), icon('chevron-left'), 'btn-sm', '', __('general.back'));

    return page_with_title(
        $link . ' ' . (
            $volunteertype->id
            ? sprintf(__('Edit %s'), htmlspecialchars((string) $volunteertype->name))
            : __('Create volunteer type')
        ),
        [
            $volunteertype->id ?
                buttons([
                    button(url('/volunteertypes'), icon('person-lines-fill') . __('volunteertypes.volunteertypes'), 'back'),
                ]) : '',
            msg(),
            form([
                div('row', [
                    div('col-md-9', [
                        $supporter_mode
                            ? form_info(__('general.name'), htmlspecialchars($volunteertype->name))
                            : form_text('name', __('general.name'), $volunteertype->name, false, 255),
                        form_textarea('description', __('general.description'), $volunteertype->description),
                        form_info('', __('Please use markdown for the description.')),
                        heading(__('Contact'), 3),
                        form_info(
                            '',
                            __('Primary contact person/desk for user questions.')
                        ),
                        form_text('contact_name', __('general.name'), $volunteertype->contact_name),
                        config('enable_dect') ? form_text('contact_dect', __('general.dect'), $volunteertype->contact_dect) : '',
                        form_text('contact_email', __('general.email'), $volunteertype->contact_email),
                    ]),

                    div('col-md-3', [
                        heading(__('State'), 3),
                        $supporter_mode
                            ? form_info(__('volunteertypes.restricted'), $volunteertype->restricted ? __('Yes') : __('No'))
                            : form_checkbox(
                                'restricted',
                                __('volunteertypes.restricted') .
                                ' <span class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" title="' .
                                __('volunteertypes.restricted.info') . '"></span>',
                                $volunteertype->restricted
                            ),
                        $supporter_mode
                            ? form_info(__('shift.self_signup'), $volunteertype->shift_self_signup ? __('Yes') : __('No'))
                            : form_checkbox(
                                'shift_self_signup',
                                __('shift.self_signup') .
                                ' <span class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" title="' .
                                __('volunteertypes.shift.self_signup.info') . '"></span>',
                                $volunteertype->shift_self_signup
                            ),
                        $requires_driving_license,
                        $requires_ifsg,
                        $supporter_mode
                            ? form_info(__('Show on dashboard'), $volunteertype->show_on_dashboard ? __('Yes') : __('No'))
                            : form_checkbox('show_on_dashboard', __('Show on dashboard'), $volunteertype->show_on_dashboard),
                        $supporter_mode
                            ? form_info(__('Hide at Registration'), $volunteertype->hide_register ? __('Yes') : __('No'))
                            : form_checkbox('hide_register', __('Hide at Registration'), $volunteertype->hide_register),
                        $supporter_mode
                            ? form_info(
                                __('volunteertypes.hide_on_shift_view'),
                                $volunteertype->hide_on_shift_view ? __('Yes') : __('No')
                            )
                            : form_checkbox(
                                'hide_on_shift_view',
                                __('volunteertypes.hide_on_shift_view') .
                                ' <span class="bi bi-info-circle-fill text-info" data-bs-toggle="tooltip" title="' .
                                __('volunteertypes.hide_on_shift_view.info') . '"></span>',
                                $volunteertype->hide_on_shift_view
                            ),
                    ]),
                ]),
                form_submit('submit', icon('save') . __('form.save')),
            ]),
        ],
        true
    );
}

/**
 * Renders the buttons for the volunteertype view.
 *
 * @param VolunteerType $volunteertype
 * @param UserVolunteerType|null $user_volunteertype
 * @param bool $admin_volunteertypes
 * @param bool $supporter
 * @param License $user_license
 * @param User|null $user
 * @return string
 */
function VolunteerType_view_buttons(
    VolunteerType $volunteertype,
    ?UserVolunteerType $user_volunteertype,
    $admin_volunteertypes,
    $supporter,
    $user_license,
    $user
) {
    if (
        config('driving_license_enabled')
        && $volunteertype->requires_driver_license
        && $user_volunteertype
    ) {
        $buttons[] = button(
            url('/settings/certificates'),
            icon('person-vcard') . __('My driving license')
        );
    }
    if (
        config('ifsg_enabled')
        && $volunteertype->requires_ifsg_certificate
        && $user_volunteertype
    ) {
        $buttons[] = button(
            url('/settings/certificates'),
            icon('card-checklist') . __('volunteertype.ifsg.own')
        );
    }

    if (is_null($user_volunteertype)) {
        $buttons[] = button(
            url('/user-volunteertypes', ['action' => 'add', 'volunteertype_id' => $volunteertype->id]),
            icon('box-arrow-in-right') . ($admin_volunteertypes ? '' : __('Join')),
            'add',
            '',
            ($admin_volunteertypes ? 'Join' : ''),
        );
    } else {
        if (
            config('driving_license_enabled')
            && $volunteertype->requires_driver_license
            && !$user_license->wantsToDrive()
        ) {
            error(__('This volunteer type requires a driver license. Please enter your driver license information!'));
        }

        if (
            config('ifsg_enabled') && $volunteertype->requires_ifsg_certificate && !(
                $user->license->ifsg_certificate_light || $user->license->ifsg_certificate
            )
        ) {
            error(__('volunteertype.ifsg.required.info'));
        }

        if ($volunteertype->restricted && !$user_volunteertype->confirm_user_id) {
            error(sprintf(
                __('You are unconfirmed for this volunteer type. Please go to the introduction for %s to get confirmed.'),
                $volunteertype->name
            ));
        }
        $buttons[] = button(
            url('/user-volunteertypes', ['action' => 'delete', 'user_volunteertype_id' => $user_volunteertype->id]),
            icon('box-arrow-right') . ($admin_volunteertypes ? '' : __('Leave')),
            '',
            '',
            ($admin_volunteertypes ? __('Leave') : ''),
        );
    }

    if ($admin_volunteertypes || $supporter) {
        $buttons[] = button(
            url('/volunteertypes', ['action' => 'edit', 'volunteertype_id' => $volunteertype->id]),
            icon('pencil'),
            '',
            '',
            __('form.edit')
        );
    }
    if ($admin_volunteertypes) {
        $buttons[] = button(
            url('/volunteertypes', ['action' => 'delete', 'volunteertype_id' => $volunteertype->id]),
            icon('trash'),
            'btn-danger',
            '',
            __('form.delete')
        );
    }

    return buttons($buttons);
}

function certificateIcon($confirmed, $certificate)
{
    return ($confirmed && $certificate)
        ? icon('check2-all', 'text-success')
        : icon_bool($certificate);
}

/**
 * Renders and sorts the members of an volunteertype into supporters, members and unconfirmed members.
 *
 * @param VolunteerType $volunteertype
 * @param User[] $members
 * @param bool $admin_user_volunteertypes
 * @param bool $admin_volunteertypes
 * @return array [supporters, members, unconfirmed members]
 */
function VolunteerType_view_members(VolunteerType $volunteertype, $members, $admin_user_volunteertypes, $admin_volunteertypes)
{
    $supporters = [];
    $members_confirmed = [];
    $members_unconfirmed = [];
    $goodie_enabled = config('goodie_type') !== GoodieType::None->value;
    $goodie_tshirt = config('goodie_type') === GoodieType::Tshirt->value;
    $tshirt_sizes = config('tshirt_sizes');
    foreach ($members as $member) {
        $member->name = User_Nick_render($member) . User_Pronoun_render($member);
        if (config('enable_dect')) {
            $member['dect'] =
                sprintf('<a href="tel:%s">%1$s</a>', htmlspecialchars((string) $member->contact->dect));
        }
        if (config('driving_license_enabled') && $volunteertype->requires_driver_license) {
            $drive_confirmed = $member->license->drive_confirmed;
            $member['wants_to_drive'] = certificateIcon($drive_confirmed, $member->license->wantsToDrive());
            $member['has_car'] = icon_bool($member->license->has_car);
            $member['has_license_car'] = certificateIcon($drive_confirmed, $member->license->drive_car);
            $member['has_license_3_5t_transporter'] = certificateIcon($drive_confirmed, $member->license->drive_3_5t);
            $member['has_license_7_5t_truck'] = certificateIcon($drive_confirmed, $member->license->drive_7_5t);
            $member['has_license_12t_truck'] = certificateIcon($drive_confirmed, $member->license->drive_12t);
            $member['has_license_forklift'] = certificateIcon($drive_confirmed, $member->license->drive_forklift);
        }
        if (config('ifsg_enabled') && $volunteertype->requires_ifsg_certificate) {
            $ifsg_confirmed = $member->license->ifsg_confirmed;
            $member['ifsg_certificate'] = certificateIcon($ifsg_confirmed, $member->license->ifsg_certificate);
            if (config('ifsg_light_enabled')) {
                $member['ifsg_certificate_light'] = certificateIcon(
                    $ifsg_confirmed,
                    $member->license->ifsg_certificate_light
                );
            }
        }
        $goodie_actions = [];
        if (
            auth()->can('volunteertype.goodie.list')
            && auth()->can('user.goodie.edit')
            && $goodie_enabled
        ) {
            $shirtSize = $goodie_tshirt
                ? ($member->personalData->shirt_size ?: '-')
                : '';
            $got_goodie_button_title = $member->state->got_goodie
                ? __('Remove goodie')
                : __('user.got_goodie');
            $goodie_actions[] = ($shirtSize !== '-') ? form(
                [
                    form_submit(
                        '',
                        $member->state->got_goodie
                            ? icon('arrow-counterclockwise')
                            : icon('gift'),
                        'btn-sm',
                        false,
                        'secondary',
                        $got_goodie_button_title
                    ),
                    form_hidden('shirt_size', $shirtSize),
                    form_hidden('arrived', $member->state->arrived),
                    form_hidden('active', $member->state->active),
                    form_hidden('got_goodie', !$member->state->got_goodie),
                ],
                url('/admin/user/' . $member->id . '/goodie'),
                false,
                true,
            ) : '';
            $goodie_actions[] = button(
                url('/admin/user/' . $member->id . '/goodie'),
                icon('pencil'),
                'btn-secondary btn-sm',
                false,
                __('user.edit.goodie'),
            );
            if ($goodie_tshirt) {
                $member['shirt_size'] = isset($tshirt_sizes[$shirtSize]) ? $tshirt_sizes[$shirtSize] : '-';
            }
            $member['goodie_actions'] = buttons($goodie_actions);
        }

        $edit_certificates = '';
        if (
            (
                config('driving_license_enabled')
                && $volunteertype->requires_driver_license
                && ($admin_user_volunteertypes || auth()->can('user.drive.edit'))
            )
            || (
                config('ifsg_enabled')
                && $volunteertype->requires_ifsg_certificate
                && ($admin_user_volunteertypes || auth()->can('user.ifsg.edit'))
            )
        ) {
            $edit_certificates =
                button(
                    url('/users/' . $member->id . '/certificates'),
                    icon('card-checklist'),
                    'btn-sm',
                    '',
                    __('Edit certificates'),
                );
        }
        if ($volunteertype->restricted && empty($member->pivot->confirm_user_id)) {
            $member['actions'] = table_buttons([
                $edit_certificates,
                button(
                    url(
                        '/user-volunteertypes',
                        ['action' => 'confirm', 'user_volunteertype_id' => $member->pivot->id]
                    ),
                    __('Confirm'),
                    'btn-sm'
                ),
                button(
                    url(
                        '/user-volunteertypes',
                        ['action' => 'delete', 'user_volunteertype_id' => $member->pivot->id]
                    ),
                    __('Deny'),
                    'btn-sm'
                ),
            ]);
            $members_unconfirmed[] = $member;
        } elseif ($member->pivot->supporter) {
            if ($admin_volunteertypes || ($admin_user_volunteertypes && config('supporters_can_promote'))) {
                $member['actions'] = table_buttons([
                    $edit_certificates,
                    button(
                        url('/user-volunteertypes', [
                            'action' => 'update',
                            'user_volunteertype_id' => $member->pivot->id,
                            'supporter' => 0,
                        ]),
                        icon('person-fill-down'),
                        'btn-sm btn-danger',
                        '',
                        __('Remove supporter rights'),
                    ),
                ]);
            } else {
                $member['actions'] = $edit_certificates
                    ? table_buttons([$edit_certificates,])
                    : '';
            }
            $supporters[] = $member;
        } else {
            if ($admin_user_volunteertypes) {
                $member['actions'] = table_buttons([
                    $edit_certificates,
                    ($admin_volunteertypes || config('supporters_can_promote')) ?
                        button(
                            url('/user-volunteertypes', [
                                'action' => 'update',
                                'user_volunteertype_id' => $member->pivot->id,
                                'supporter' => 1,
                            ]),
                            icon('person-fill-up'),
                            'btn-sm',
                            '',
                            __('Add supporter rights'),
                        ) :
                        '',
                    button(
                        url('/user-volunteertypes', [
                            'action' => 'delete',
                            'user_volunteertype_id' => $member->pivot->id,
                        ]),
                        icon('trash'),
                        'btn-sm btn-danger',
                        '',
                        __('Remove'),
                    ),
                ]);
            } elseif ($edit_certificates) {
                $member['actions'] = table_buttons([
                    $edit_certificates,
                ]);
            }
            $members_confirmed[] = $member;
        }
    }

    return [
        $supporters,
        $members_confirmed,
        $members_unconfirmed,
    ];
}

/**
 * Creates the needed member table headers according to given rights and settings from the volunteertype.
 *
 * @param VolunteerType $volunteertype
 * @param bool $supporter
 * @param bool $admin_volunteertypes
 * @return array
 */
function VolunteerType_view_table_headers(VolunteerType $volunteertype, $supporter, $admin_volunteertypes)
{
    $goodie_enabled = config('goodie_type') !== GoodieType::None->value;
    $goodie_tshirt = config('goodie_type') === GoodieType::Tshirt->value;
    $headers = [
        'name' => __('general.nick'),
    ];

    if (config('enable_dect')) {
        $headers['dect'] = __('general.dect');
    }

    if (
        config('driving_license_enabled') && $volunteertype->requires_driver_license
        && ($supporter || $admin_volunteertypes || auth()->can('user.drive.edit'))
    ) {
        $headers = array_merge($headers, [
            'wants_to_drive' => __('Driver'),
            'has_car' => __('Has car'),
            'has_license_car' => __('settings.certificates.drive_car'),
            'has_license_3_5t_transporter' => __('settings.certificates.drive_3_5t'),
            'has_license_7_5t_truck' => __('settings.certificates.drive_7_5t'),
            'has_license_12t_truck' => __('settings.certificates.drive_12t'),
            'has_license_forklift' => __('settings.certificates.drive_forklift'),
        ]);
    }

    if (
        config('ifsg_enabled') && $volunteertype->requires_ifsg_certificate
        && ($supporter || $admin_volunteertypes || auth()->can('user.ifsg.edit'))
    ) {
        if (config('ifsg_light_enabled')) {
            $headers['ifsg_certificate_light'] = __('ifsg.certificate_light');
        }
        $headers['ifsg_certificate'] = __('ifsg.certificate');
    }
    if (
        $goodie_enabled
        && auth()->can('volunteertype.goodie.list')
        && auth()->can('user.goodie.edit')
    ) {
        if ($goodie_tshirt) {
            $headers['shirt_size'] = __('user.shirt_size');
        }
        $headers['goodie_actions'] = __('Goodie actions');
    }
    $headers['actions'] = '';

    return $headers;
}

/**
 * Render an volunteertype page containing the member lists.
 *
 * @param VolunteerType $volunteertype
 * @param User[] $members
 * @param UserVolunteerType|null $user_volunteertype
 * @param bool $admin_user_volunteertypes
 * @param bool $admin_volunteertypes
 * @param bool $supporter
 * @param License $user_license
 * @param User $user
 * @param ShiftsFilterRenderer $shiftsFilterRenderer
 * @param ShiftCalendarRenderer $shiftCalendarRenderer
 * @param int $tab The selected tab
 * @return string
 */
function VolunteerType_view(
    VolunteerType $volunteertype,
    $members,
    ?UserVolunteerType $user_volunteertype,
    $admin_user_volunteertypes,
    $admin_volunteertypes,
    $supporter,
    $user_license,
    $user,
    ShiftsFilterRenderer $shiftsFilterRenderer,
    ShiftCalendarRenderer $shiftCalendarRenderer,
    $tab
) {
    $back = button(url('/volunteertypes'), icon('chevron-left'), 'btn-sm', '', __('general.back'));
    $add = (($admin_volunteertypes || $admin_user_volunteertypes) ? button(
        url('/user-volunteertypes', ['action' => 'add', 'volunteertype_id' => $volunteertype->id]),
        icon('plus-lg'),
        'btn-sm',
        '',
        __('general.add')
    ) : '');
    return page_with_title(
        $back . ' ' . sprintf(__('Team %s'), htmlspecialchars($volunteertype->name)) . ' ' . $add,
        [
            VolunteerType_view_buttons($volunteertype, $user_volunteertype, $admin_volunteertypes, $supporter, $user_license, $user),
            msg(),
            tabs([
                __('general.info') => VolunteerType_view_info(
                    $volunteertype,
                    $members,
                    $admin_user_volunteertypes,
                    $admin_volunteertypes,
                    $supporter
                ),
                __('general.shifts') => VolunteerType_view_shifts(
                    $volunteertype,
                    $shiftsFilterRenderer,
                    $shiftCalendarRenderer
                ),
            ], $tab),
        ],
        true
    );
}

/**
 * @param VolunteerType $volunteertype
 * @param ShiftsFilterRenderer $shiftsFilterRenderer
 * @param ShiftCalendarRenderer $shiftCalendarRenderer
 * @return string HTML
 */
function VolunteerType_view_shifts(VolunteerType $volunteertype, $shiftsFilterRenderer, $shiftCalendarRenderer)
{
    $shifts = $shiftsFilterRenderer->render(url('/volunteertypes', [
        'action' => 'view',
        'volunteertype_id' => $volunteertype->id,
    ]), ['type' => $volunteertype->id]);
    $shifts .= $shiftCalendarRenderer->render();

    return div('first', $shifts);
}

/**
 * @param VolunteerType $volunteertype
 * @param User[] $members
 * @param bool $admin_user_volunteertypes
 * @param bool $admin_volunteertypes
 * @param bool $supporter
 * @return string HTML
 */
function VolunteerType_view_info(
    VolunteerType $volunteertype,
    $members,
    $admin_user_volunteertypes,
    $admin_volunteertypes,
    $supporter
) {
    $required_info_show = !auth()->user()
            ->userVolunteerTypes()
            ->where('volunteer_types.id', $volunteertype->id)
            ->count()
        && !$admin_volunteertypes
        && !$admin_user_volunteertypes
        && !$supporter;
    $info = [];
    if ($volunteertype->hasContactInfo()) {
        $info[] = VolunteerTypes_render_contact_info($volunteertype);
    }

    $info[] = '<h3>' . __('general.description') . '</h3>';
    $parsedown = new Parsedown();
    if ($volunteertype->description != '') {
        $info[] = $parsedown->parse(htmlspecialchars($volunteertype->description));
    }
    if ($volunteertype->requires_ifsg_certificate && $required_info_show) {
        $info[] = info(__('volunteertype.ifsg.required.info.preview'), true);
    }
    if ($volunteertype->requires_driver_license && $required_info_show) {
        $info[] = info(__('volunteertype.driving_license.required.info.preview'), true);
    }

    list($supporters, $members_confirmed, $members_unconfirmed) = VolunteerType_view_members(
        $volunteertype,
        $members,
        $admin_user_volunteertypes,
        $admin_volunteertypes
    );
    $table_headers = VolunteerType_view_table_headers($volunteertype, $supporter, $admin_volunteertypes);

    if (count($supporters) > 0) {
        $info[] = '<h3>' . __('Supporters') . '</h3>';
        $info[] = table($table_headers, $supporters);
    }

    if (count($members_confirmed) > 0) {
        $members_confirmed[] = [
            'name' => __('Sum'),
            'dect' => count($members_confirmed),
            'actions' => '',
        ];
    }

    if (count($members_unconfirmed) > 0) {
        $members_unconfirmed[] = [
            'name' => __('Sum'),
            'dect' => count($members_unconfirmed),
            'actions' => '',
        ];
    }

    $add = (($admin_volunteertypes || $admin_user_volunteertypes) ? button(
        url('/user-volunteertypes', ['action' => 'add', 'volunteertype_id' => $volunteertype->id]),
        icon('plus-lg'),
        'btn-sm',
        '',
        __('general.add')
    ) : '');
    $info[] = '<h3>' . __('Members') . ' ' . $add . '</h3>';
    $info[] = table($table_headers, $members_confirmed);

    if ($admin_user_volunteertypes && $volunteertype->restricted && count($members_unconfirmed) > 0) {
        $info[] = '<h3>' . __('Unconfirmed') . '</h3>';
        $info[] = buttons([
            button(
                url('/user-volunteertypes', ['action' => 'confirm_all', 'volunteertype_id' => $volunteertype->id]),
                icon('check-lg') . __('Confirm all')
            ),
            button(
                url('/user-volunteertypes', ['action' => 'delete_all', 'volunteertype_id' => $volunteertype->id]),
                icon('trash') . __('Deny all')
            ),
        ]);
        $info[] = table($table_headers, $members_unconfirmed);
    }

    return join($info);
}

/**
 * Renders the contact info
 *
 * @param VolunteerType $volunteertype
 * @return string HTML
 */
function VolunteerTypes_render_contact_info(VolunteerType $volunteertype)
{
    $info = [
        __('general.name') => [
            htmlspecialchars($volunteertype->contact_name),
            htmlspecialchars($volunteertype->contact_name),
        ],
        __('general.dect') => config('enable_dect')
            ? [
                sprintf('<a href="tel:%s">%1$s</a>', htmlspecialchars($volunteertype->contact_dect)),
                htmlspecialchars($volunteertype->contact_dect),
            ]
            : null,
        __('general.email') => [
            sprintf('<a href="mailto:%s">%1$s</a>', htmlspecialchars($volunteertype->contact_email)),
            htmlspecialchars($volunteertype->contact_email),
        ],
    ];
    $contactInfo = [];
    foreach ($info as $name => $data) {
        if (!empty($data[1])) {
            $contactInfo[$name] = $data[0];
        }
    }

    return heading(__('Contact'), 3) . description($contactInfo);
}

/**
 * Display the list of volunteertypes.
 *
 * @param VolunteerType[]|Collection $volunteertypes
 * @param bool $admin_volunteertypes
 * @return string
 */
function VolunteerTypes_list_view($volunteertypes, bool $admin_volunteertypes)
{
    $add = button(
        url('/volunteertypes', ['action' => 'edit']),
        icon('plus-lg'),
        'btn-sm',
        '',
        __('general.add')
    );
    return page_with_title(
        volunteertypes_title() . ' ' . ($admin_volunteertypes ? $add : ''),
        [
            msg(),
            buttons([
                button(url('/volunteertypes/about'), __('volunteertypes.about')),
            ]),
            table([
                'name' => __('general.name'),
                'is_restricted' => icon('mortarboard-fill') . __('volunteertypes.restricted'),
                'shift_self_signup_allowed' => icon('pencil-square') . __('shift.self_signup.allowed'),
                'membership' => __('Membership'),
                'actions' => '',
            ], $volunteertypes),
        ],
        true,
    );
}
