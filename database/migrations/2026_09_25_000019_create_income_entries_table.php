<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rendimentos: dividendos de FII, juros, JCP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->date('reference_month');             // sempre o dia 01 do mês
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('type', 20);                  // App\Enums\IncomeType: dividend, interest, jcp
            $table->timestamps();

            $table->index(['asset_id', 'payment_date']);
            $table->index('payment_date');               // relatório mensal de rendimentos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_entries');
    }
};
