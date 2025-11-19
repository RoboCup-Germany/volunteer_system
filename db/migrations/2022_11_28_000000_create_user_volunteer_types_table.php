<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use stdClass;

class CreateUserVolunteerTypesTable extends Migration
{
    use ChangesReferences;
    use Reference;

    /**
     * Creates the new table, copies the data and drops the old one
     */
    public function up(): void
    {
        $connection = $this->schema->getConnection();

        $this->schema->create('user_volunteer_type', function (Blueprint $table): void {
            $table->increments('id');
            $this->referencesUser($table);
            $this->references($table, 'volunteer_types')->index();
            $this->references($table, 'users', 'confirm_user_id')->nullable()->index();
            $table->boolean('supporter')->default(false)->index();
            $table->index(['user_id', 'volunteer_type_id', 'confirm_user_id']);
            $table->unique(['user_id', 'volunteer_type_id']);
        });

        if (!$this->schema->hasTable('UserVolunteerTypes')) {
            return;
        }

        /** @var stdClass[] $records */
        $records = $connection
            ->table('UserVolunteerTypes')
            ->get();
        foreach ($records as $record) {
            $connection->table('user_volunteer_type')->insert([
                'id'              => $record->id,
                'user_id'         => $record->user_id,
                'volunteer_type_id'   => $record->volunteertype_id,
                'confirm_user_id' => $record->confirm_user_id ?: null,
                'supporter'       => (bool) $record->supporter,
            ]);
        }

        $this->changeReferences(
            'UserVolunteerTypes',
            'id',
            'user_volunteer_type',
            'id'
        );

        $this->schema->drop('UserVolunteerTypes');
    }

    /**
     * Recreates the previous table, copies the data and drops the new one
     */
    public function down(): void
    {
        $connection = $this->schema->getConnection();

        $this->schema->create('UserVolunteerTypes', function (Blueprint $table): void {
            $table->increments('id');
            $this->referencesUser($table);
            $this->references($table, 'volunteer_types', 'volunteertype_id')->index('volunteertype_id');
            $this->references($table, 'users', 'confirm_user_id')->nullable()->index('confirm_user_id');
            $table->boolean('supporter')->nullable()->index('coordinator');
            $table->index(['user_id', 'volunteertype_id', 'confirm_user_id'], 'user_id');
        });

        /** @var stdClass[] $records */
        $records = $connection
            ->table('user_volunteer_type')
            ->get();
        foreach ($records as $record) {
            $connection->table('UserVolunteerTypes')->insert([
                'id'              => $record->id,
                'user_id'         => $record->user_id,
                'volunteertype_id'    => $record->volunteer_type_id,
                'confirm_user_id' => $record->confirm_user_id ?: null,
                'supporter'       => (bool) $record->supporter,
            ]);
        }

        $this->changeReferences(
            'user_volunteer_type',
            'id',
            'UserVolunteerTypes',
            'id',
            'integer'
        );

        $this->schema->drop('user_volunteer_type');
    }
}
