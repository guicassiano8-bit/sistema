<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de compras. Criada antes de rewards, que aponta para ela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 20);              // App\Enums\ShoppingCategory: daily, urgent, important, not_important
            $table->decimal('estimated_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('link', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending'); // App\Enums\ShoppingStatus: pending, purchased
            $table->timestamp('purchased_at')->nullable();
            $table->decimal('actual_price', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
    }
};
