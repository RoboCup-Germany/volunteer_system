<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class AddVolunteertypeGoodieListPermission extends Migration
{
    protected Connection $db;
    protected int $goodieManager = 50;

    public function __construct(SchemaBuilder $schema)
    {
        parent::__construct($schema);
        $this->db = $this->schema->getConnection();
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->db->table('privileges')
            ->insertOrIgnore([
                'name' => 'volunteertype.goodie.list',
                'description' => 'Add edit goodies to volunteer type view',
            ]);

        $volunteertypeGoodieList = $this->db->table('privileges')
            ->where('name', 'volunteertype.goodie.list')
            ->get(['id'])
            ->first();

        $this->db->table('group_privileges')
            ->insertOrIgnore([
                ['group_id' => $this->goodieManager, 'privilege_id' => $volunteertypeGoodieList->id],
            ]);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->table('privileges')
            ->where('name', 'volunteertype.goodie.list')
            ->delete();
    }
}
