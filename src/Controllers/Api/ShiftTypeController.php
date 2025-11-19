<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\ShiftTypeResource;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\Shifts\ShiftType;

class ShiftTypeController extends ApiController
{
    use UsesAuth;

    public function index(): Response
    {
        $models = ShiftType::query()
            ->orderBy('name')
            ->get();

        $data = ['data' => ShiftTypeResource::collection($models)];
        return $this->response
            ->withContent(json_encode($data));
    }
}
