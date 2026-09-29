<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotografia mensal da carteira, gravada pelo job de fim de mês.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('reference_month');             // sempre o dia 01 do mês
            $table->decimal('invested_amount', 15, 2);
            $table->decimal('market_value', 15, 2);
            $table->timestamps();

            $table->unique(['asset_id', 'reference_month']);
            $table->index('reference_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
