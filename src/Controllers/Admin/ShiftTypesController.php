<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Admin;

use Volunteersystem\Controllers\BaseController;
use Volunteersystem\Controllers\HasUserNotifications;
use Volunteersystem\Http\Exceptions\ValidationException;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\ShiftType;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

class ShiftTypesController extends BaseController
{
    use HasUserNotifications;

    /** @var array<string> */
    protected array $permissions = [
        'shifttypes.view',
        'edit' => 'shifttypes.edit',
        'delete' => 'shifttypes.edit',
        'save' => 'shifttypes.edit',
    ];

    public function __construct(
        protected LoggerInterface $log,
        protected ShiftType $shiftType,
        protected Redirector $redirect,
        protected Response $response
    ) {
    }

    public function index(): Response
    {
        $shiftTypes = $this->shiftType
            ->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);

        return $this->response->withView(
            'admin/shifttypes/index',
            ['shifttypes' => $shiftTypes, 'is_index' => true]
        );
    }

    public function edit(Request $request): Response
    {
        $shiftTypeId = (int) $request->getAttribute('shift_type_id');

        $shiftType = $this->shiftType->find($shiftTypeId);
        $volunteertypes = VolunteerType::all()
            ->sortBy('name');

        return $this->response->withView(
            'admin/shifttypes/edit',
            [
                'shifttype' => $shiftType,
                'volunteer_types' => $volunteertypes,
            ]
        );
    }

    public function view(Request $request): Response
    {
        $shiftTypeId = (int) $request->getAttribute('shift_type_id');
        /** @var ShiftType $shiftType */
        $shiftType = $this->shiftType->findOrFail($shiftTypeId);

        $days = $shiftType->shifts()
            ->scopes('needsUsers')
            ->selectRaw('DATE(start) AS date')
            ->orderBy('date')
            ->groupBy('date')
            ->pluck('date');

        $day = $request->get('day');
        $day = $days->contains($day) ? $day : $days->first();

        $shifts = $shiftType->shifts()
            ->with([
                'neededVolunteerTypes.volunteerType',
                'schedule',
                'shiftEntries.user.personalData',
                'shiftEntries.user.state',
                'shiftEntries.volunteerType',
                'shiftType.neededVolunteerTypes.volunteerType',
                'location.neededVolunteerTypes.volunteerType',
            ])
            ->whereDate('start', $day)
            ->orderBy('start')
            ->get();

        return $this->response->withView(
            'admin/shifttypes/view',
            [
                'shifttype' => $shiftType,
                'is_view' => true,
                'shifts_active' => $request->has('shifts') || $request->get('day'),
                'days' => $days,
                'selected_day' => $day,
                'shifts' => $shifts,
            ]
        );
    }

    public function save(Request $request): Response
    {
        $shiftTypeId = (int) $request->getAttribute('shift_type_id');

        /** @var ShiftType $shiftType */
        $shiftType = $this->shiftType->findOrNew($shiftTypeId);

        if ($request->request->has('delete')) {
            return $this->delete($request);
        }

        /** @var Collection|VolunteerType[] $volunteerTypes */
        $volunteerTypes = VolunteerType::all();
        $validation = [];
        foreach ($volunteerTypes as $volunteerType) {
            $validation['volunteer_type_' . $volunteerType->id] = 'optional|int';
        }

        $data = $this->validate(
            $request,
            [
                'name' => 'required|max:255',
                'description' => 'optional',
                'signup_advance_hours' => 'optional|float',
            ] + $validation
        );

        if (ShiftType::whereName($data['name'])->where('id', '!=', $shiftType->id)->exists()) {
            throw new ValidationException((new Validator())->addErrors(['name' => ['validation.name.exists']]));
        }

        $shiftType->name = $data['name'];
        $shiftType->description = $data['description'] ?? '';
        $shiftType->signup_advance_hours = $data['signup_advance_hours'] ?: null;

        $shiftType->save();
        $shiftType->neededVolunteerTypes()->delete();

        // Associate volunteer types with the shift type
        $volunteersInfo = '';
        foreach ($volunteerTypes as $volunteerType) {
            $count = $data['volunteer_type_' . $volunteerType->id];
            if (!$count) {
                continue;
            }

            $neededVolunteerType = new NeededVolunteerType();

            $neededVolunteerType->shiftType()->associate($shiftType);
            $neededVolunteerType->volunteerType()->associate($volunteerType);

            $neededVolunteerType->count = $data['volunteer_type_' . $volunteerType->id];

            $neededVolunteerType->save();

            $volunteersInfo .= sprintf(', %s: %s', $volunteerType->name, $count);
        }

        $this->log->info(
            'Saved shift type "{name}" ({id}): {description}, {signup_advance_hours}, {volunteers}',
            [
                'id' => $shiftType->id,
                'name' => $shiftType->name,
                'description' => $shiftType->description,
                'signup_advance_hours' => $shiftType->signup_advance_hours,
                'volunteers' => $volunteersInfo,
            ]
        );

        $this->addNotification('shifttype.edit.success');

        return $this->redirect->to('/admin/shifttypes');
    }

    public function delete(Request $request): Response
    {
        $data = $this->validate($request, [
            'id' => 'required|int',
            'delete' => 'checked',
        ]);

        $shiftType = $this->shiftType->findOrFail($data['id']);

        $shifts = $shiftType->shifts;
        foreach ($shifts as $shift) {
            event('shift.deleting', ['shift' => $shift]);
        }
        $shiftType->delete();

        $this->log->info('Deleted shift type {name} ({id})', ['name' => $shiftType->name, 'id' => $shiftType->id]);
        $this->addNotification('shifttype.delete.success');

        return $this->redirect->to('/admin/shifttypes');
    }
}
