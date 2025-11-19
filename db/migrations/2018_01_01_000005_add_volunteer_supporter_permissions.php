<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;

class AddVolunteerSupporterPermissions extends Migration
{
    /** @var string[] */
    protected array $data = [
        '2-Volunteer',
        'shiftentry_edit_volunteertype_supporter',
    ];

    /**
     * Run the migration
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('GroupPrivileges')) {
            return;
        }

        $db = $this->schema->getConnection();
        if (!empty($db->select($this->getQuery('SELECT *'), $this->data))) {
            return;
        }

        // Add permissions to volunteers to edit volunteers if they are volunteertype supporters
        $db->insert(
            '
                INSERT IGNORE INTO GroupPrivileges (group_id, privilege_id)
                VALUES ((SELECT UID FROM `Groups` WHERE `name` = ?), (SELECT id FROM `Privileges` WHERE `name` = ?))
            ',
            $this->data
        );
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        if (!$this->schema->hasTable('GroupPrivileges')) {
            return;
        }

        // Remove permission from volunteers
        $this->schema->getConnection()->delete(
            $this->getQuery('DELETE'),
            $this->data
        );
    }

    private function getQuery(string $type): string
    {
        return sprintf('
                %s FROM GroupPrivileges
                WHERE group_id = (SELECT UID FROM `Groups` WHERE `name` = ?)
                AND privilege_id = (SELECT id FROM `Privileges` WHERE `name` = ?)
        ', $type);
    }
}
