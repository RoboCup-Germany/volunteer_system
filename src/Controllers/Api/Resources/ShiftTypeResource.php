<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\Shifts\ShiftType;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class ShiftTypeResource extends BasicResource
{
    protected Collection | BaseModel | Pivot | ShiftType $model;

    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->name,
            'description' => $this->model->description,
        ];
    }
}
