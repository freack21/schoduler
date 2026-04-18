<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            // Max jam per hari per kelas — kontrol persebaran mapel
            // Misal MTK 4 jam/minggu, max_jam_per_hari=2 → tersebar min 2 hari (2+2)
            $table->unsignedTinyInteger('max_jam_per_hari')->default(2)->after('jam_per_minggu');
        });
    }

    public function down(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->dropColumn('max_jam_per_hari');
        });
    }
};
