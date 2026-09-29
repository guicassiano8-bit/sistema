<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Livro-razão de pontos. Saldo = SUM(amount).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');                   // positivo (crédito) ou negativo (débito)
            $table->string('type', 30);                  // App\Enums\PointTransactionType: task_completed, task_reverted, reward_redeemed, adjustment
            $table->nullableMorphs('source');            // source_type + source_id + índice. Nulo em ajustes manuais.
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
