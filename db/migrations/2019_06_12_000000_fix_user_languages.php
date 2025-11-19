<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;

class FixUserLanguages extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $connection = $this->schema->getConnection();
        $connection
            ->table('users_settings')
            ->update([
                'language' => $connection->raw('REPLACE(language, ".UTF-8", "")'),
            ]);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $connection = $this->schema->getConnection();
        $connection
            ->table('users_settings')
            ->update([
                'language' => $connection->raw('CONCAT(language, ".UTF-8")'),
            ]);
    }
}
