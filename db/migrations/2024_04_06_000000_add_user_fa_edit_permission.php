<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;

class AddUserFaEditPermission extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $db = $this->schema->getConnection();
        $db->table('privileges')
            ->insert([
                'name' => 'user.fa.edit', 'description' => 'Edit User Force Active State',
            ]);

        $editFa = $db->table('privileges')
            ->where('name', 'user.fa.edit')
            ->get(['id'])
            ->first();

        $bureaucrat = 80;
        $db->table('group_privileges')
            ->insertOrIgnore([
                ['group_id' => $bureaucrat, 'privilege_id' => $editFa->id],
            ]);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $db = $this->schema->getConnection();
        $db->table('privileges')
            ->where('name', 'user.fa.edit')
            ->delete();
    }
}
