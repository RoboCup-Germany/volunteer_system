<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Controllers\BaseController;
use Volunteersystem\Http\Response;

abstract class ApiController extends BaseController
{
    public array $permissions = [
        'api',
    ];

    public function __construct(protected Response $response)
    {
        $this->response = $this->response
            ->withHeader('content-type', 'application/json')
            // Using * here to "skip" all other headers on browser requests
            ->withHeader('access-control-allow-origin', '*');
    }
}
