<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers;

use Volunteersystem\Http\Response;
use Volunteersystem\Models\VolunteerType;

class VolunteerTypesController extends BaseController
{
    public function __construct(protected Response $response)
    {
    }

    public function about(): Response
    {
        $volunteertypes = VolunteerType::all();

        return $this->response->withView(
            'pages/volunteertypes/about',
            ['volunteertypes' => $volunteertypes]
        );
    }
}
