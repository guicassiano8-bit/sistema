<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Molde das tarefas recorrentes. O job diário gera as linhas em tasks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->string('frequency', 20);             // App\Enums\Frequency: daily, weekly, monthly, yearly
            $table->unsignedSmallInteger('interval')->default(1); // a cada N dias/semanas/meses
            $table->json('days_of_week')->nullable();    // ex.: [1,3,5] = seg, qua, sex (ISO: 1 = segunda)
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_tasks');
    }
};
