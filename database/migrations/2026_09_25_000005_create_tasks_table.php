<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarefas avulsas e ocorrências de tarefas recorrentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_task_id')->nullable()
                  ->constrained()->nullOnDelete();       // apagar o molde não apaga o histórico
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->date('scheduled_date');              // data atual no calendário (muda ao transferir)
            $table->date('occurrence_date')->nullable(); // data prevista pela regra (nunca muda)
            $table->date('original_date');               // primeira data agendada
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status', 20)->default('pending'); // App\Enums\TaskStatus: pending, done, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('rescheduled_count')->default(0);
            $table->timestamps();

            $table->index(['scheduled_date', 'status']); // filtro do calendário
            // Impede o job de gerar a mesma ocorrência duas vezes.
            // Tarefas avulsas têm recurring_task_id NULL, e o MySQL aceita vários NULL num índice único.
            $table->unique(['recurring_task_id', 'occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
