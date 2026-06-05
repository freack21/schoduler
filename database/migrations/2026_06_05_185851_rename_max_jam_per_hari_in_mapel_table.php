<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->renameColumn('max_jam_per_hari', 'jam_per_hari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->renameColumn('jam_per_hari', 'max_jam_per_hari');
        });
    }
};
