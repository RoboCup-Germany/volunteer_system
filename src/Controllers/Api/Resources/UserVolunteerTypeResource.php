<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\VolunteerType;

class UserVolunteerTypeResource extends VolunteerTypeResource
{
    public function toArray(): array
    {
        /** @var VolunteerType $volunteerType */
        $volunteerType = $this->model;
        /** @var VolunteerType $volunteerType */
        $userVolunteerType = $this->model->pivot;

        return [
            'volunteertype' => VolunteerTypeResource::toIdentifierArray($volunteerType),
            'confirmed' => !$volunteerType->restricted
                || $userVolunteerType->supporter
                || $userVolunteerType->confirm_user_id,
            'supporter' => $userVolunteerType->supporter,
        ];
    }
}
