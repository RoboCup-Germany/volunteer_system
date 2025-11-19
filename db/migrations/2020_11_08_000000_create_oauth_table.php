<?php

declare(strict_types=1);

namespace Volunteersystem\Migrations;

use Volunteersystem\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauthTable extends Migration
{
    use Reference;

    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->create('oauth', function (Blueprint $table): void {
            $table->increments('id');
            $this->referencesUser($table);
            $table->string('provider');
            $table->string('identifier');
            $table->unique(['provider', 'identifier']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->drop('oauth');
    }
}
