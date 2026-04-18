<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jam_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->integer('jam_ke'); // 1, 2, 3, ...
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->boolean('is_piket')->default(false); // break flag
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_pelajaran');
    }
};
