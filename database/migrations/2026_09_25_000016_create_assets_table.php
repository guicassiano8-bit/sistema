<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada investimento da carteira.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('ticker', 12)->nullable()->unique(); // ex.: MXRF11
            $table->string('institution')->nullable();
            $table->string('indexer', 10)->nullable();   // App\Enums\Indexer: CDI, IPCA, PRE
            $table->decimal('rate', 8, 4)->nullable();   // ex.: 110.0000 (% do CDI) ou 6.5000 (IPCA + 6,5%)
            $table->date('maturity_date')->nullable();
            $table->json('metadata')->nullable();        // campos específicos de tipos futuros
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
