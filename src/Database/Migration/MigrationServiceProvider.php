<?php

declare(strict_types=1);

namespace Volunteersystem\Database\Migration;

use Volunteersystem\Container\ServiceProvider;
use Volunteersystem\Database\Database;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class MigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var Database $database */
        $database = $this->app->get(Database::class);
        $schema = $database->getConnection()->getSchemaBuilder();

        $this->app->instance('db.schema', $schema);
        $this->app->bind(SchemaBuilder::class, 'db.schema');

        $migration = $this->app->make(Migrate::class);
        $this->app->instance('db.migration', $migration);
    }
}
