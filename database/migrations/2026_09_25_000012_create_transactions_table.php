<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ganhos e gastos, incluindo parcelas de compras no cartão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('finance_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('recurring_transaction_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->string('type', 20);                  // income, expense
            $table->string('description');
            $table->decimal('amount', 15, 2);            // sempre positivo; o sinal vem de type
            $table->date('date');                        // data da compra ou da parcela
            $table->string('payment_method', 20)->nullable(); // App\Enums\PaymentMethod: pix, debit, credit, cash, bank_slip, transfer
            $table->string('status', 20)->default('paid');    // App\Enums\TransactionStatus: paid, pending

            // Parcelamento: todas as parcelas de uma compra compartilham o mesmo UUID.
            $table->uuid('installment_group_id')->nullable()->index();
            $table->unsignedTinyInteger('installment_number')->nullable(); // ex.: 2
            $table->unsignedTinyInteger('installment_total')->nullable();  // ex.: 6

            $table->nullableMorphs('source');            // ex.: ShoppingItem que originou o gasto
            $table->timestamps();

            $table->index(['account_id', 'date']);       // extrato e saldo por conta
            $table->index(['type', 'date']);             // relatórios mensais
            $table->index(['finance_category_id', 'date']); // gastos por categoria e orçamento
            $table->unique(['recurring_transaction_id', 'date']); // o job não duplica lançamentos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
