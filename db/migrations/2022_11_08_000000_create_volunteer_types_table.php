<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use stdClass;

class CreateVolunteerTypesTable extends Migration
{
    use ChangesReferences;

    /**
     * Creates the new table, copies the data and drops the old one
     */
    public function up(): void
    {
        $connection = $this->schema->getConnection();
        $this->schema->create('volunteer_types', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
            $table->text('description')->default('');

            $table->string('contact_name')->default('');
            $table->string('contact_dect')->default('');
            $table->string('contact_email')->default('');

            $table->boolean('restricted')->default(false);
            $table->boolean('requires_driver_license')->default(false);
            $table->boolean('no_self_signup')->default(false);
            $table->boolean('show_on_dashboard')->default(true);
            $table->boolean('hide_register')->default(false);
        });

        if (!$this->schema->hasTable('VolunteerTypes')) {
            return;
        }

        /** @var Collection|stdClass[] $records */
        $records = $connection
            ->table('VolunteerTypes')
            ->get();
        foreach ($records as $record) {
            $connection->table('volunteer_types')->insert([
                'id'          => $record->id,
                'name'        => $record->name,
                'description' => $record->description,

                'contact_name'  => (string) $record->contact_name,
                'contact_dect'  => (string) $record->contact_dect,
                'contact_email' => (string) $record->contact_email,

                'restricted'              => $record->restricted,
                'requires_driver_license' => $record->requires_driver_license,
                'no_self_signup'          => $record->no_self_signup,
                'show_on_dashboard'       => $record->show_on_dashboard,
                'hide_register'           => $record->hide_register,
            ]);
        }

        $this->changeReferences(
            'VolunteerTypes',
            'id',
            'volunteer_types',
            'id'
        );

        $this->schema->drop('VolunteerTypes');
    }

    /**
     * Recreates the previous table, copies the data and drops the new one
     */
    public function down(): void
    {
        $connection = $this->schema->getConnection();
        $this->schema->create('VolunteerTypes', function (Blueprint $table): void {
            $table->integer('id', true);
            $table->string('name', 50)->default('')->unique();
            $table->boolean('restricted');
            $table->mediumText('description');
            $table->boolean('requires_driver_license');
            $table->boolean('no_self_signup');
            $table->string('contact_name', 250)->nullable();
            $table->string('contact_dect', 40)->nullable();
            $table->string('contact_email', 250)->nullable();
            $table->boolean('show_on_dashboard');
            $table->boolean('hide_register')->default(false);
        });

        /** @var Collection|stdClass[] $records */
        $records = $connection
            ->table('volunteer_types')
            ->get();
        foreach ($records as $record) {
            $connection->table('VolunteerTypes')->insert([
                'id'          => $record->id,
                'name'        => $record->name,
                'description' => $record->description,

                'contact_name'  => $record->contact_name ?: null,
                'contact_dect'  => $record->contact_dect ?: null,
                'contact_email' => $record->contact_email ?: null,

                'restricted'              => $record->restricted,
                'requires_driver_license' => $record->requires_driver_license,
                'no_self_signup'          => $record->no_self_signup,
                'show_on_dashboard'       => $record->show_on_dashboard,
                'hide_register'           => $record->hide_register,
            ]);
        }

        $this->changeReferences(
            'volunteer_types',
            'id',
            'VolunteerTypes',
            'id',
            'integer'
        );

        $this->schema->drop('volunteer_types');
    }
}
