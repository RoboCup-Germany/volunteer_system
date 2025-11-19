<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\Location;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class LocationResource extends BasicResource
{
    protected Collection | BaseModel | Pivot | Location $model;

    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description ?: '',
            'url' => url('/locations', ['action' => 'view', 'location_id' => $this->model->id]),
        ];
    }
}
