<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Truncate tables first because old data won't be valid
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('jadwal')->truncate();
        DB::table('jam_pelajaran')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::table('jam_pelajaran', function (Blueprint $table) {
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])->after('id');
            $table->integer('durasi_menit')->default(45)->after('is_istirahat');
        });
    }

    public function down(): void
    {
        Schema::table('jam_pelajaran', function (Blueprint $table) {
            $table->dropColumn(['hari', 'durasi_menit']);
        });
    }
};
