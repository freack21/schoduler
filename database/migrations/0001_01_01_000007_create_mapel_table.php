<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapel', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // MTK, BIO, FIS
            $table->string('nama'); // Matematika, Biologi, Fisika
            $table->integer('jam_per_minggu')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapel');
    }
};
