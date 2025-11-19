<?php

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\News;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\ShiftsFilter;

/**
 * Loads all data for the public dashboard
 *
 * @return array
 */
function public_dashboard_controller()
{
    $filter = null;
    if (request()->get('filtered')) {
        $requestLocations = check_request_int_array('locations');
        $requestVolunteerTypes = check_request_int_array('types');

        if (!$requestLocations && !$requestVolunteerTypes) {
            $sessionFilter = collect(session()->get('shifts-filter', []));
            $requestLocations = $sessionFilter->get('locations', []);
            $requestVolunteerTypes = $sessionFilter->get('types', []);
        }

        $volunteerTypes = collect(unrestricted_volunteertypes());
        $locations = $requestLocations ?: Location::orderBy('name')->get()->pluck('id')->toArray();
        $volunteerTypes = $requestVolunteerTypes ?: $volunteerTypes->pluck('id')->toArray();
        $filterValues = [
            'userShiftsAdmin' => false,
            'filled'          => [],
            'locations'       => $locations,
            'types'           => $volunteerTypes,
            'startTime'       => null,
            'endTime'         => null,
        ];

        $filter = new ShiftsFilter();
        $filter->sessionImport($filterValues);
    }

    $stats = [
        'needed-3-hours' => stats_volunteers_needed_three_hours($filter),
        'needed-night'   => stats_volunteers_needed_for_nightshifts($filter),
        'volunteers-working' => stats_currently_working($filter),
        'hours-to-work'  => stats_hours_to_work($filter),
    ];

    $free_shifts_source = Shifts_free(time(), time() + 12 * 60 * 60, $filter);
    $free_shifts = [];
    foreach ($free_shifts_source as $shift) {
        $shift = Shift($shift);
        $free_shift = public_dashboard_controller_free_shift($shift, $filter);
        if (count($free_shift['needed_volunteers']) > 0) {
            $free_shifts[] = $free_shift;
        }
    }

    $highlighted_news = News::whereIsHighlighted(true)
        ->orderBy('updated_at')
        ->limit(1)
        ->get();

    return [
        __('Public Dashboard'),
        public_dashboard_view($stats, $free_shifts, $highlighted_news),
    ];
}

/**
 * Gathers information for free shifts to display.
 *
 * @param Shift             $shift
 * @param ShiftsFilter|null $filter
 *
 * @return array
 */
function public_dashboard_controller_free_shift(Shift $shift, ?ShiftsFilter $filter = null)
{
    // ToDo move to model and return one
    $free_shift = [
        'id'             => $shift->id,
        'style'          => 'default',
        'start'          => $shift->start->format('H:i'),
        'end'            => $shift->end->format('H:i'),
        'duration'       => round(($shift->end->timestamp - $shift->start->timestamp) / 3600),
        'shifttype_name' => $shift->shiftType->name,
        'title'          => $shift->title,
        'location_name'  => $shift->location->name,
        'needed_volunteers'  => public_dashboard_needed_volunteers($shift->neededVolunteers, $filter),
    ];

    if (time() + 3 * 60 * 60 > $shift->start->timestamp) {
        $free_shift['style'] = 'warning';
    }
    if (time() > $shift->start->timestamp) {
        $free_shift['style'] = 'danger';
    }

    return $free_shift;
}

/**
 * Gathers information for needed volunteers on dashboard
 *
 * @param array             $needed_volunteers
 * @param ShiftsFilter|null $filter
 *
 * @return array
 */
function public_dashboard_needed_volunteers($needed_volunteers, ?ShiftsFilter $filter = null)
{
    $result = [];
    foreach ($needed_volunteers as $needed_volunteer) {
        $need = $needed_volunteer['count'] - $needed_volunteer['taken'];
        if ($need > 0 && (!$filter || in_array($needed_volunteer['volunteer_type_id'], $filter->getTypes()))) {
            $volunteertype = VolunteerType::find($needed_volunteer['volunteer_type_id']);
            if ($volunteertype->show_on_dashboard) {
                $result[] = [
                    'need'           => $need,
                    'volunteertype_name' => $volunteertype->name,
                ];
            }
        }
    }
    return $result;
}

/**
 * Returns url to public dashboard
 *
 * @param array $parameters
 *
 * @return string
 */
function public_dashboard_link(array $parameters = []): string
{
    return url('/public-dashboard', $parameters);
}
