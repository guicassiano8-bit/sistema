<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contas: corrente, carteira, corretora e cartão de crédito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // ex.: Nubank, Carteira, Inter Invest
            $table->string('type', 20);                  // App\Enums\AccountType: checking, wallet, brokerage, credit_card
            $table->string('institution')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->unsignedTinyInteger('closing_day')->nullable(); // só cartão
            $table->unsignedTinyInteger('due_day')->nullable();     // só cartão
            $table->decimal('credit_limit', 15, 2)->nullable();     // só cartão
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
