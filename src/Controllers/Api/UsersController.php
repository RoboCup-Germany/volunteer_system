<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\UserVolunteerTypeReferenceResource;
use Volunteersystem\Controllers\Api\Resources\UserDetailResource;
use Volunteersystem\Controllers\Api\Resources\UserResource;
use Volunteersystem\Controllers\Api\Resources\WorklogResource;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Illuminate\Database\Eloquent\Collection;

class UsersController extends ApiController
{
    use UsesAuth;

    public function index(): Response
    {
        $models = User::query()
            ->orderBy('name')
            ->get();

        $models = $models->map(function (BaseModel $model) {
            return UserResource::toIdentifierArray($model);
        });

        $data = ['data' => $models];
        return $this->response
            ->withContent(json_encode($data));
    }

    public function user(Request $request): Response
    {
        $id = $request->getAttribute('user_id');
        $user = $this->getUser($id);

        $userData = $user->id == $this->auth->user()->id ? new UserDetailResource($user) : new UserResource($user);
        $data = ['data' => $userData->toArray()];
        return $this->response
            ->withContent(json_encode($data));
    }

    public function entriesByVolunteertype(Request $request): Response
    {
        $id = (int) $request->getAttribute('volunteertype_id');
        /** @var VolunteerType $volunteerType */
        $volunteerType = VolunteerType::findOrFail($id);

        /** @var User[]|Collection $models */
        $models = $volunteerType->userVolunteerTypes()
            ->orderBy('name')
            ->get();

        /** @var UserVolunteerType[]|Collection $models */
        $models = $models->map(function (User $model) {
            // Patch to use the existing user model instead of a partially populated one
            $model->pivot->setRelatedModel($model);
            return $model->pivot;
        });

        /** @var UserVolunteerTypeReferenceResource[]|Collection $models */
        $models = UserVolunteerTypeReferenceResource::collection($models);

        $data = ['data' => $models];
        return $this->response
            ->withContent(json_encode($data));
    }

    public function worklogs(Request $request): Response
    {
        $id = (int) $request->getAttribute('user_id');
        /** @var User $user */
        $user = User::findOrFail($id);

        $models = $user->worklogs();

        $models = $models
            ->orderBy('worked_at')
            ->get();

        $models = WorklogResource::collection($models);

        $data = ['data' => $models];
        return $this->response
            ->withContent(json_encode($data));
    }
}
