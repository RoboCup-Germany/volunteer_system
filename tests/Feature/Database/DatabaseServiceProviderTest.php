<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Feature\Database;

use Volunteersystem\Config\Config;
use Volunteersystem\Database\Database;
use Volunteersystem\Database\DatabaseServiceProvider;

class DatabaseServiceProviderTest extends DatabaseTest
{
    /**
     * @covers \Volunteersystem\Database\DatabaseServiceProvider::register()
     */
    public function testRegister(): void
    {
        $this->app->instance('config', new Config([
            'database' => $this->getDbConfig(),
            'timezone' => 'UTC',
        ]));

        $serviceProvider = new DatabaseServiceProvider($this->app);
        $serviceProvider->register();
        $this->assertTrue($this->app->has(Database::class));
    }
}
