<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddHideOnShiftViewToVolunteertypes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('volunteer_types', function (Blueprint $table): void {
            $table->boolean('hide_on_shift_view')->default(false)->after('hide_register');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('volunteer_types', function (Blueprint $table): void {
            $table->dropColumn('hide_on_shift_view');
        });
    }
}
