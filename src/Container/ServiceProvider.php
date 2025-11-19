<?php

declare(strict_types=1);

namespace Volunteersystem\Container;

use Volunteersystem\Application;

abstract class ServiceProvider
{
    /**
     * ServiceProvider constructor.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register container bindings
     */
    public function register(): void
    {
    }

    /**
     * Called after other services had been registered
     */
    public function boot(): void
    {
    }
}
