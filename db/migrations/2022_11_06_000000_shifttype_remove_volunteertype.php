<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ShifttypeRemoveVolunteertype extends Migration
{
    use Reference;

    /**
     * Run the migration
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('ShiftTypes')) {
            return;
        }

        $this->schema->table('ShiftTypes', function (Blueprint $table): void {
            $table->dropForeign('shifttypes_ibfk_1');
            $table->dropColumn('volunteertype_id');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        if (!$this->schema->hasTable('ShiftTypes')) {
            return;
        }

        $this->schema->table('ShiftTypes', function (Blueprint $table): void {
            $table->integer('volunteertype_id')->after('name')->index()->nullable();
            $this->addReference($table, 'volunteertype_id', 'VolunteerTypes', null, 'shifttypes_ibfk_1');
        });
    }
}
