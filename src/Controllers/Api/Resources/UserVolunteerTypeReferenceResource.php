<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api\Resources;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;

class UserVolunteerTypeReferenceResource extends BasicResource
{
    public function toArray(): array
    {
        /** @var User $user */
        $user = $this->model->pivotRelated;
        /** @var VolunteerType $volunteerType */
        $volunteerType = $this->model->pivotParent;
        /** @var UserVolunteerType $userVolunteerType */
        $userVolunteerType = $this->model;

        return [
            'user' => UserResource::toIdentifierArray($user),
            'confirmed' => !$volunteerType->restricted
                || $userVolunteerType->supporter
                || $userVolunteerType->confirm_user_id,
            'supporter' => $userVolunteerType->supporter,
        ];
    }
}
