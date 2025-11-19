<?php

declare(strict_types=1);

namespace Volunteersystem\Http\Validation;

use Volunteersystem\Application;
use Volunteersystem\Container\ServiceProvider;
use Volunteersystem\Controllers\BaseController;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $validator = $this->app->make(Validator::class);
        $this->app->instance(Validator::class, $validator);
        $this->app->instance('validator', $validator);

        $this->app->afterResolving(function ($object, Application $app): void {
            if (!$object instanceof BaseController) {
                return;
            }

            $object->setValidator($app->get(Validator::class));
        });
    }
}
