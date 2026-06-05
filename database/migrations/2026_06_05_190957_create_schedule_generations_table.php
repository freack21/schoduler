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
        Schema::create('schedule_generations', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('idle'); // idle, starting, running, done, error
            $table->integer('generation')->default(0);
            $table->float('fitness', 8, 6)->default(0);
            $table->integer('violations')->default(0);
            $table->integer('dist_violations')->default(0);
            $table->string('message')->nullable();
            $table->integer('max_generations')->default(100);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_generations');
    }
};
