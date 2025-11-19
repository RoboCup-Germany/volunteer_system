<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddHideRegisterToVolunteertypes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('VolunteerTypes')) {
            return;
        }

        $this->schema->table('VolunteerTypes', function (Blueprint $table): void {
            $table->boolean('hide_register')->default(false)->after('show_on_dashboard');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        if (!$this->schema->hasTable('VolunteerTypes')) {
            return;
        }

        $this->schema->table('VolunteerTypes', function (Blueprint $table): void {
            $table->dropColumn('hide_register');
        });
    }
}
