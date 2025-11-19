<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers;

use Volunteersystem\Config\Config;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Redirector;
use Volunteersystem\Http\Response;

class HomeController extends BaseController
{
    public function __construct(protected Authenticator $auth, protected Config $config, protected Redirector $redirect)
    {
    }

    public function index(): Response
    {
        return $this->redirect->to($this->auth->user() ? $this->config->get('home_site') : 'login');
    }
}
