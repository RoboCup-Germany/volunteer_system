<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\LocationResource;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\Location;

class LocationsController extends ApiController
{
    public function index(): Response
    {
        $models = Location::query()
            ->orderBy('name')
            ->get();

        $data = ['data' => LocationResource::collection($models)];
        return $this->response
            ->withContent(json_encode($data));
    }
}
