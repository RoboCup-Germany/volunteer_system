<?php

namespace Volunteersystem;

use Volunteersystem\Config\GoodieType;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Shifts\ShiftSignupStatus;
use Volunteersystem\Models\User\User;
use Illuminate\Support\Collection;

use function theme_type;

/**
 * Renders a single shift for the shift calendar
 */
class ShiftCalendarShiftRenderer
{
    /**
     * Renders a shift
     *
     * @param Shift                   $shift The shift to render
     * @param VolunteerType[]|Collection  $needed_volunteertypes
     * @param ShiftEntry[]|Collection $shift_entries
     * @param User                    $user The user who is viewing the shift calendar
     * @return array
     */
    public function render(Shift $shift, $needed_volunteertypes, $shift_entries, $user)
    {
        $info_text = '';
        if ($shift->title != '') {
            $info_text = icon('info-circle') . htmlspecialchars($shift->title) . '<br>';
        }
        list($shift_signup_state, $shifts_row) = $this->renderShiftNeededVolunteertypes(
            $shift,
            $needed_volunteertypes,
            $shift_entries,
            $user
        );

        $class = $this->classForSignupState($shift_signup_state);

        $blocks = ceil(($shift->end->timestamp - $shift->start->timestamp) / ShiftCalendarRenderer::SECONDS_PER_ROW);
        $blocks = max(1, $blocks);

        return [
            $blocks,
            div(
                'shift-card" style="height: '
                . ($blocks * ShiftCalendarRenderer::BLOCK_HEIGHT - ShiftCalendarRenderer::MARGIN)
                . 'px;',
                div(
                    'shift card bg-' . $class,
                    [
                        $this->renderShiftHead($shift, $class, $shift_signup_state->getFreeEntries()),
                        div('card-body ' . $this->classBg(), [
                            $info_text,
                            location_name_render($shift->location),
                        ]),
                        $shifts_row,
                    ]
                )
            ),
        ];
    }

    /**
     * @param ShiftSignupState $shiftSignupState
     * @return string
     */
    private function classForSignupState(ShiftSignupState $shiftSignupState)
    {
        return match ($shiftSignupState->getState()) {
            ShiftSignupStatus::ADMIN, ShiftSignupStatus::OCCUPIED => 'success',
            ShiftSignupStatus::SIGNED_UP => 'primary',
            ShiftSignupStatus::NOT_ARRIVED, ShiftSignupStatus::NOT_YET, ShiftSignupStatus::SHIFT_ENDED => 'secondary',
            ShiftSignupStatus::VOLUNTEERTYPE, ShiftSignupStatus::COLLIDES => 'warning',
            ShiftSignupStatus::FREE => 'danger',
            default => 'light',
        };
    }

    /**
     * @param Shift                   $shift
     * @param VolunteerType[]|Collection  $needed_volunteertypes
     * @param ShiftEntry[]|Collection $shift_entries
     * @param User                    $user
     * @return array
     */
    private function renderShiftNeededVolunteertypes(Shift $shift, $needed_volunteertypes, $shift_entries, $user)
    {
        $shift_entries_filtered = [];
        foreach ($needed_volunteertypes as $needed_volunteertype) {
            $shift_entries_filtered[$needed_volunteertype['id']] = [];
        }
        foreach ($shift_entries as $shift_entry) {
            $shift_entries_filtered[$shift_entry->volunteer_type_id][] = $shift_entry;
        }

        $html = '';
        /** @var ShiftSignupState $shift_signup_state */
        $shift_signup_state = null;
        foreach ($needed_volunteertypes as $volunteertype) {
            if ($volunteertype['count'] > 0 || count($shift_entries_filtered[$volunteertype['id']]) > 0) {
                list($volunteertype_signup_state, $volunteertype_html) = $this->renderShiftNeededVolunteertype(
                    $shift,
                    $shift_entries_filtered[$volunteertype['id']],
                    $volunteertype,
                    $user
                );
                if (is_null($shift_signup_state)) {
                    $shift_signup_state = $volunteertype_signup_state;
                } else {
                    $shift_signup_state->combineWith($volunteertype_signup_state);
                }
                $html .= $volunteertype_html;
            }
        }
        if (is_null($shift_signup_state)) {
            $shift_signup_state = new ShiftSignupState(ShiftSignupStatus::SHIFT_ENDED, 0);
        }

        if (auth()->can('user_shifts_admin')) {
            $html .= '<li class="list-group-item d-flex align-items-center ' . $this->classBg() . '">';
            $html .= button(
                shift_entry_create_link_admin($shift),
                icon('plus-lg') . __('Add more volunteers'),
                'btn-sm'
            );
            $html .= '</li>';
        }
        if ($html != '') {
            return [
                $shift_signup_state,
                '<ul class="list-group list-group-flush">' . $html . '</ul>',
            ];
        }

        return [
            $shift_signup_state,
            '',
        ];
    }

    /**
     * Renders a list entry containing the needed volunteers for an volunteertype
     *
     * @param Shift                   $shift The shift which is rendered
     * @param ShiftEntry[]|Collection $shift_entries
     * @param array                   $volunteertype The volunteertype, containing information about needed volunteertypes
     *                           and already signed up volunteers
     * @param User                    $user The user who is viewing the shift calendar
     * @return array
     */
    private function renderShiftNeededVolunteertype(Shift $shift, $shift_entries, $volunteertype, $user)
    {
        $volunteertype = (new VolunteerType())->forceFill($volunteertype);
        $entry_list = [];
        foreach ($shift_entries as $entry) {
            $class = $entry->freeloaded_by ? 'text-decoration-line-through' : '';
            $entry_list[] = '<span class="text-nowrap ' . $class . '">' . User_Nick_render($entry->user) . '</span>';
        }
        $shift_signup_state = Shift_signup_allowed(
            $user,
            $shift,
            $volunteertype,
            null,
            null,
            $volunteertype,
            $shift_entries
        );
        $shift_can_signup = Shift_signup_allowed_volunteer(
            $user,
            $shift,
            $volunteertype,
            null,
            null,
            $volunteertype,
            $shift_entries
        );
        $freeEntriesCount = $shift_signup_state->getFreeEntries();
        $inner_text = _e('%d helper needed', '%d helpers needed', $freeEntriesCount, [$freeEntriesCount]);

        $entry = match ($shift_signup_state->getState()) {
            // When admin or free display a link + button for sign up
            ShiftSignupStatus::ADMIN, ShiftSignupStatus::FREE =>
                '<a class="me-1 text-nowrap" href="'
                . shift_entry_create_link($shift, $volunteertype)
                . '">'
                . $inner_text
                . '</a> '
                . button(
                    shift_entry_create_link($shift, $volunteertype),
                    __('Sign up'),
                    'btn-sm btn-primary text-nowrap d-print-none'
                ),
            // No link and add a text hint, when the shift ended
            ShiftSignupStatus::SHIFT_ENDED => $inner_text . ' (' . __('ended') . ')',
            // No link and add a text hint, when the shift ended
            ShiftSignupStatus::NOT_ARRIVED => $inner_text . ' (' . __('please arrive for signup') . ')',
            ShiftSignupStatus::NOT_YET => $inner_text . ' (' . __('not yet possible') . ')',
            ShiftSignupStatus::VOLUNTEERTYPE => $volunteertype->restricted || !$volunteertype->shift_self_signup
                // User has to be confirmed on the volunteertype first or can't sign up by themselves
                ? $inner_text . icon('mortarboard-fill')
                // Add link to join the volunteertype first
                : $inner_text . '<br />'
                . button(
                    url('/user-volunteertypes', ['action' => 'add', 'volunteertype_id' => $volunteertype->id]),
                    sprintf(__('Join %s'), htmlspecialchars($volunteertype->name)),
                    'btn-sm'
                ),
            // Shift collides or user is already signed up: No signup allowed
            ShiftSignupStatus::COLLIDES, ShiftSignupStatus::SIGNED_UP => $inner_text,
            // Shift is full
            ShiftSignupStatus::OCCUPIED => null,
            default => null,
        };
        if (!is_null($entry)) {
            $entry_list[] = $entry;
        }

        $shifts_row = '<li class="list-group-item d-flex flex-wrap align-items-center ' . $this->classBg() . '">';
        $shifts_row .= '<strong class="me-1">' . VolunteerType_name_render($volunteertype) . ':</strong> ';
        $shifts_row .= join(', ', $entry_list);
        $shifts_row .= '</li>';
        return [
            $shift_can_signup,
            $shifts_row,
        ];
    }

    /**
     * Return the corresponding bg class
     *
     * @return string
     */
    private function classBg(): string
    {
        if (theme_type() === 'light') {
            return 'bg-white';
        }

        return 'bg-dark';
    }

    /**
     * Renders the shift header
     *
     * @param Shift  $shift The shift
     * @param string $class The shift state class
     * @return string
     */
    private function renderShiftHead(Shift $shift, $class, $needed_volunteertypes_count)
    {
        $goodie = GoodieType::from(config('goodie_type'));
        $goodie_enabled = $goodie !== GoodieType::None;

        $header_buttons = '';
        if (auth()->can('admin_shifts')) {
            $header_buttons = div('ms-auto d-print-none d-flex', [
                    button(
                        url('/user-shifts', ['edit_shift' => $shift->id]),
                        icon('pencil'),
                        'btn-' . $class . ' btn-sm border-light text-white',
                        '',
                        __('form.edit')
                    ),
                    form([
                        form_hidden('delete_shift', $shift->id),
                        form_submit(
                            'delete',
                            icon('trash'),
                            'btn-sm border-light text-white ms-1',
                            false,
                            'danger',
                            __('form.delete'),
                            [
                                'confirm_submit_title' => __('Do you want to delete the shift "%s" from %s to %s?', [
                                    $shift->shiftType->name,
                                    $shift->start->format(__('general.datetime')),
                                    $shift->end->format(__('H:i')),
                                ]),
                                'confirm_button_text' => icon('trash') . __('form.delete'),
                            ]
                        ),
                    ], url('/user-shifts', ['delete_shift' => $shift->id])),
                ]);
        }
        $night_shift = '';
        if ($shift->isNightShift() && $goodie_enabled) {
            $night_shift = ' <i class="bi-moon-stars"></i>';
        }

        $shift_heading = '<span>'
            . $shift->start->format('H:i') . ' &dash; '
            . $shift->end->format('H:i') . ' &mdash; '
            . htmlspecialchars($shift->shiftType->name)
            . $night_shift
            . '</span>';

        if ($needed_volunteertypes_count > 0) {
            $shift_heading = '<span class="badge bg-light text-danger me-1">' . $needed_volunteertypes_count . '</span> ' . $shift_heading;
        }

        return div('card-header d-flex align-items-center', [
            '<a class="d-flex align-items-center text-white" href="' . shift_link($shift) . '">' . $shift_heading . '</a>',
            $header_buttons,
        ]);
    }
}
