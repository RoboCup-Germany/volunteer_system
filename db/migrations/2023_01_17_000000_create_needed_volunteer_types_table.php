<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use stdClass;

class CreateNeededVolunteerTypesTable extends Migration
{
    use ChangesReferences;
    use Reference;

    /**
     * Creates the new table, copies the data and drops the old one
     */
    public function up(): void
    {
        $connection = $this->schema->getConnection();
        $previous = $this->schema->hasTable('NeededVolunteerTypes');

        $this->schema->create('needed_volunteer_types', function (Blueprint $table): void {
            $table->increments('id');
            $this->references($table, 'rooms')->nullable();
            $this->references($table, 'shifts')->nullable();
            $this->references($table, 'volunteer_types');
            $table->integer('count')->index();

            $table->index(['room_id', 'volunteer_type_id']);
        });

        if (!$previous) {
            return;
        }

        // Delete old entries which don't need volunteers
        $connection
            ->table('NeededVolunteerTypes')
            ->where('count', 0)
            ->delete();

        /** @var stdClass[] $records */
        $records = $connection
            ->table('NeededVolunteerTypes')
            ->get();
        foreach ($records as $record) {
            $connection->table('needed_volunteer_types')->insert([
                'id'            => $record->id,
                'room_id'       => $record->room_id,
                'shift_id'      => $record->shift_id,
                'volunteer_type_id' => $record->volunteer_type_id,
                'count'         => $record->count,
            ]);
        }

        $this->changeReferences(
            'NeededVolunteerTypes',
            'id',
            'needed_volunteer_types',
            'id'
        );

        $this->schema->drop('NeededVolunteerTypes');
    }

    /**
     * Recreates the previous table, copies the data and drops the new one
     */
    public function down(): void
    {
        $connection = $this->schema->getConnection();

        $this->schema->create('NeededVolunteerTypes', function (Blueprint $table): void {
            $table->increments('id');
            $this->references($table, 'rooms')->nullable();
            $this->references($table, 'shifts')->nullable();
            $this->references($table, 'volunteer_types');
            $table->integer('count')->index();

            $table->index(['room_id', 'volunteer_type_id']);
        });

        /** @var stdClass[] $records */
        $records = $connection
            ->table('needed_volunteer_types')
            ->get();
        foreach ($records as $record) {
            $connection->table('NeededVolunteerTypes')->insert([
                'id'            => $record->id,
                'room_id'       => $record->room_id,
                'shift_id'      => $record->shift_id,
                'volunteer_type_id' => $record->volunteer_type_id,
                'count'         => $record->count,
            ]);
        }

        $this->changeReferences(
            'needed_volunteer_types',
            'id',
            'NeededVolunteerTypes',
            'id'
        );

        $this->schema->drop('needed_volunteer_types');
    }
}
