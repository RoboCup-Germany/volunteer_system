<?php

declare(strict_types=1);

namespace Volunteersystem\Http;

use Volunteersystem\Container\ServiceProvider;

class RedirectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('redirect', Redirector::class);
    }
}
