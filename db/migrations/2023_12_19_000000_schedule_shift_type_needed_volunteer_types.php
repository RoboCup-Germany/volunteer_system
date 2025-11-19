<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ScheduleShiftTypeNeededVolunteerTypes extends Migration
{
    use Reference;

    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('schedules', function (Blueprint $table): void {
            $table->boolean('needed_from_shift_type')->after('shift_type')->default(false);
        });
        $this->schema->table('needed_volunteer_types', function (Blueprint $table): void {
            $this->references($table, 'shift_types')->after('shift_id')->nullable();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('schedules', function (Blueprint $table): void {
            $table->dropColumn('needed_from_shift_type');
        });
        $this->schema->table('needed_volunteer_types', function (Blueprint $table): void {
            $table->dropForeign('needed_volunteer_types_shift_type_id_foreign');
            $table->dropColumn('shift_type_id');
        });
    }
}
