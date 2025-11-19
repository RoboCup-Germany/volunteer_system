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
use Volunteersystem\Models\Location;
use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

class LocationsController extends BaseController
{
    use HasUserNotifications;

    /** @var array<string> */
    protected array $permissions = [
        'locations.view',
        'edit' => 'locations.edit',
        'save' => 'locations.edit',
        'delete' => 'locations.edit',
    ];

    public function __construct(
        protected LoggerInterface $log,
        protected Location $location,
        protected Redirector $redirect,
        protected Response $response
    ) {
    }

    public function index(): Response
    {
        $locations = $this->location
            ->withCount('shifts')
            ->orderBy('name')
            ->get();

        return $this->response->withView(
            'pages/locations/index',
            [
                'locations' => $locations,
                'is_index' => true,
            ]
        );
    }

    public function edit(Request $request): Response
    {
        $locationId = (int) $request->getAttribute('location_id');

        $location = $this->location->find($locationId);

        return $this->showEdit($location);
    }

    public function save(Request $request): Response
    {
        $locationId = (int) $request->getAttribute('location_id');

        /** @var Location $location */
        $location = $this->location->findOrNew($locationId);
        /** @var Collection|VolunteerType[] $volunteerTypes */
        $volunteerTypes = VolunteerType::all();
        $validation = [];
        foreach ($volunteerTypes as $volunteerType) {
            $validation['volunteer_type_' . $volunteerType->id] = 'optional|int';
        }

        if ($request->request->has('delete')) {
            return $this->delete($request);
        }

        $data = $this->validate(
            $request,
            [
                'name'        => 'required|max:35',
                'description' => 'optional',
                'dect'        => 'optional',
                'map_url'     => 'optional|url|max:300',
            ] + $validation
        );

        if (Location::whereName($data['name'])->where('id', '!=', $location->id)->exists()) {
            throw new ValidationException((new Validator())->addErrors(['name' => ['validation.name.exists']]));
        }

        $location->name = $data['name'];
        $location->description = $data['description'];
        $location->dect = $data['dect'];
        $location->map_url = $data['map_url'];

        $location->save();
        $location->neededVolunteerTypes()->getQuery()->delete();
        $volunteersInfo = '';

        // Associate volunteer types with the room
        foreach ($volunteerTypes as $volunteerType) {
            $count = $data['volunteer_type_' . $volunteerType->id];
            if (!$count) {
                continue;
            }

            $neededVolunteerType = new NeededVolunteerType();

            $neededVolunteerType->location()->associate($location);
            $neededVolunteerType->volunteerType()->associate($volunteerType);

            $neededVolunteerType->count = $data['volunteer_type_' . $volunteerType->id];

            $neededVolunteerType->save();

            $volunteersInfo .= sprintf(', %s: %s', $volunteerType->name, $count);
        }

        $this->log->info(
            'Updated location "{name}" ({id}): {description} {dect} {map_url} {volunteers}',
            [
                'id'          => $location->id,
                'name'        => $location->name,
                'description' => $location->description,
                'dect'        => $location->dect,
                'map_url'     => $location->map_url,
                'volunteers'      => $volunteersInfo,
            ]
        );

        $this->addNotification('location.edit.success');

        return $this->redirect->to('/locations');
    }

    public function delete(Request $request): Response
    {
        $data = $this->validate($request, [
            'id'     => 'required|int',
            'delete' => 'checked',
        ]);

        $location = $this->location->findOrFail($data['id']);

        $shifts = $location->shifts;
        foreach ($shifts as $shift) {
            event('shift.deleting', ['shift' => $shift]);
        }
        $location->delete();

        $this->log->info('Deleted location {location}', ['location' => $location->name]);
        $this->addNotification('location.delete.success');

        return $this->redirect->to('/locations');
    }

    protected function showEdit(?Location $location): Response
    {
        $volunteertypes = VolunteerType::all()
            ->sortBy('name');

        return $this->response->withView(
            'admin/locations/edit',
            [
                'location' => $location,
                'volunteer_types' => $volunteertypes,
                'needed_volunteer_types' => $location?->neededVolunteerTypes,
            ]
        );
    }
}
