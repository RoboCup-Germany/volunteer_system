<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class VolunteerTypeResource extends BasicResource
{
    protected Collection | BaseModel | Pivot | VolunteerType $model;

    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
            'restricted' => $this->model->restricted,
            'url' => url('/volunteertypes', ['action' => 'view', 'volunteertype_id' => $this->model->id]),
        ];
    }
}
