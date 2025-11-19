<?php

declare(strict_types=1);

namespace Volunteersystem\Helpers;

use Volunteersystem\Container\ServiceProvider;

class VersionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(Version::class)
            ->needs('$storage')
            ->give($this->app->get('path.storage.app'));
        $this->app->when(Version::class)
            ->needs('$gitRoot')
            ->give($this->app->get('path') . DIRECTORY_SEPARATOR . '.git');
    }
}
