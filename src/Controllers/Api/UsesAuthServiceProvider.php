<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Api;

use Volunteersystem\Application;
use Volunteersystem\Container\ServiceProvider;
use Volunteersystem\Helpers\Authenticator;

class UsesAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->afterResolving(function ($object, Application $app): void {
            if (!$object instanceof ApiController || !method_exists($object, 'setAuth')) {
                return;
            }

            /** @var UsesAuth $object */
            $object->setAuth($app->get(Authenticator::class));
        });
    }
}
