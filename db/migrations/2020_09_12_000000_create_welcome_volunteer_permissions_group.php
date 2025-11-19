<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;

class CreateWelcomeVolunteerPermissionsGroup extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('Groups')) {
            return;
        }

        $db = $this->schema->getConnection();

        $db
            ->table('Groups')
            ->insert([
                'UID'  => -25,
                'Name' => 'Welcome Volunteer',
            ]);

        $privilege = $db->table('Privileges')
            ->where('name', 'admin_arrive')
            ->first();

        $db->table('GroupPrivileges')
            ->insert([
                'group_id'     => -25,
                'privilege_id' => $privilege->id,
            ]);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        if (!$this->schema->hasTable('Groups')) {
            return;
        }

        $this->schema->getConnection()
            ->table('Groups')
            ->where('UID', -25)
            ->delete();
    }
}
