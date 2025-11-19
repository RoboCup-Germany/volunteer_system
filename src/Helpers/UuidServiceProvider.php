<?php

declare(strict_types=1);

namespace Volunteersystem\Helpers;

use Volunteersystem\Container\ServiceProvider;
use Illuminate\Support\Str;

class UuidServiceProvider extends ServiceProvider
{
    /**
     * Register the UUID generator to the Str class
     */
    public function register(): void
    {
        Str::createUuidsUsing(Uuid::class . '::uuid');
    }
}
