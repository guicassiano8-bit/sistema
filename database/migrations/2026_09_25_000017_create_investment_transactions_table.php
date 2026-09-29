<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compras, vendas, aportes e resgates de cada ativo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->nullable()
                  ->constrained()->restrictOnDelete();   // conta que pagou o aporte ou recebeu o resgate
            $table->string('type', 20);                  // App\Enums\InvestmentTransactionType: buy, sell, deposit, withdrawal
            $table->date('date');
            $table->decimal('quantity', 15, 6)->nullable();   // cotas (FII); nulo em CDB
            $table->decimal('unit_price', 15, 6)->nullable();
            $table->decimal('total', 15, 2);
            $table->decimal('fees', 15, 2)->default(0);

            // Preenchidos só em vendas, congelando o cálculo daquele momento.
            $table->decimal('average_cost', 15, 6)->nullable();
            $table->decimal('realized_profit', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'date']);
            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
