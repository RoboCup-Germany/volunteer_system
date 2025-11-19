<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddEmailNewsToUsersSettings extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('users_settings', function (Blueprint $table): void {
            $table->boolean('email_news')->default(false)->after('email_shiftinfo');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('users_settings', function (Blueprint $table): void {
            $table->dropColumn('email_news');
        });
    }
}
