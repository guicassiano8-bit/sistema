<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotações diárias dos ativos negociados em bolsa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('price', 15, 6);
            $table->string('source', 20)->default('manual'); // App\Enums\QuoteSource: manual, brapi
            $table->timestamps();

            $table->unique(['asset_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
