<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\News;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class NewsResource extends BasicResource
{
    protected Collection | BaseModel | Pivot | News $model;

    public function toArray(): array
    {
        return [
            'id' => $this->model->id,
            'name' => $this->model->title,
            'text' => $this->model->text,
            'is_meeting' => $this->model->is_meeting,
            'is_pinned' => $this->model->is_pinned,
            'is_highlighted' => $this->model->is_highlighted,
            'created_at' => $this->model->created_at,
            'updated_at' => $this->model->updated_at,
            'url' => url('/news/' . $this->model->id),
        ];
    }
}
