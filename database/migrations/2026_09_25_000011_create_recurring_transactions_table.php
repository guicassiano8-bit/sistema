<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Molde de lançamentos recorrentes: salário, aluguel, assinaturas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('finance_category_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);                  // income, expense
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 20)->nullable(); // App\Enums\PaymentMethod
            $table->string('frequency', 20)->default('monthly'); // monthly, yearly
            $table->unsignedTinyInteger('day_of_month');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
