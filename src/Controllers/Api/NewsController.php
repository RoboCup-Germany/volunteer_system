<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\Api\Resources\NewsResource;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\News;

class NewsController extends ApiController
{
    public function index(): Response
    {
        $models = News::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $data = ['data' => NewsResource::collection($models)];
        return $this->response
            ->withContent(json_encode($data));
    }
}
