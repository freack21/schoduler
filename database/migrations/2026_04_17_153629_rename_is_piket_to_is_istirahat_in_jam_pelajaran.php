<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jam_pelajaran', function (Blueprint $table) {
            $table->renameColumn('is_piket', 'is_istirahat');
        });
    }

    public function down(): void
    {
        Schema::table('jam_pelajaran', function (Blueprint $table) {
            $table->renameColumn('is_istirahat', 'is_piket');
        });
    }
};
