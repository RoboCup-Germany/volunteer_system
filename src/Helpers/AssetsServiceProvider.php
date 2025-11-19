<?php

declare(strict_types=1);

namespace Volunteersystem\Helpers;

use Volunteersystem\Container\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(Assets::class)
            ->needs('$assetsPath')
            ->give($this->app->get('path.assets.public'));
    }
}
