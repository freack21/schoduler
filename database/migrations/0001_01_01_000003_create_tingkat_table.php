<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tingkat', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // X, XI, XII
            $table->string('kode')->unique(); // 10, 11, 12
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tingkat');
    }
};
