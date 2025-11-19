<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\VolunteerTypeResource;
use Volunteersystem\Controllers\Api\Resources\UserVolunteerTypeResource;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;

class VolunteerTypeController extends ApiController
{
    use UsesAuth;

    public function index(): Response
    {
        $models = VolunteerType::query()
            ->orderBy('name')
            ->get();

        $data = ['data' => VolunteerTypeResource::collection($models)];
        return $this->response
            ->withContent(json_encode($data));
    }

    public function ofUser(Request $request): Response
    {
        $id = $request->getAttribute('user_id');
        $user = $this->getUser($id);

        $data = ['data' => UserVolunteerTypeResource::collection($user->userVolunteerTypes)];

        return $this->response
            ->withContent(json_encode($data));
    }
}
