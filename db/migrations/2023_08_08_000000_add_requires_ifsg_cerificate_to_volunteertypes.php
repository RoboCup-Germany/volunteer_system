<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddRequiresIfsgCerificateToVolunteertypes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->table('volunteer_types', function (Blueprint $table): void {
            $table->boolean('requires_ifsg_certificate')->default(false)->after('requires_driver_license');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->table('volunteer_types', function (Blueprint $table): void {
            $table->dropColumn('requires_ifsg_certificate');
        });
    }
}
