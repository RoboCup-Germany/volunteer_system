<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Illuminate\Contracts\Support\Arrayable;

class ShiftWithEntriesResource extends ShiftResource
{
    public function toArray(array | Arrayable $location = [], array | Arrayable $volunteerTypes = []): array
    {
        return [
            ...parent::toArray($location),
            'needed_volunteer_types' => $volunteerTypes instanceof Arrayable ? $volunteerTypes->toArray() : $volunteerTypes,
        ];
    }
}
