<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangeUsersContactDectFieldSize extends Migration
{
    protected array $tables = [
        'VolunteerTypes'    => 'contact_dect',
        'users_contact' => 'dect',
    ];

    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->changeDectTo(40);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->changeDectTo(5);
    }

    private function changeDectTo(int $length): void
    {
        foreach ($this->tables as $table => $column) {
            if (!$this->schema->hasTable($table)) {
                continue;
            }

            $this->schema->table($table, function (Blueprint $table) use ($column, $length): void {
                $table->string($column, $length)->nullable()->change();
            });
        }
    }
}
