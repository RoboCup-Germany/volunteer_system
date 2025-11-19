<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class RenameGoodyToGoodie extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('users_settings', function (Blueprint $table): void {
            $table->renameColumn('email_goody', 'email_goodie');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('users_settings', function (Blueprint $table): void {
            $table->renameColumn('email_goodie', 'email_goody');
        });
    }
}
