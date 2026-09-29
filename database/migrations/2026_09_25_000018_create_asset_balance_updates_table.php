<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saldo informado à mão (valor mostrado pelo banco), usado para CDBs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_balance_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('reference_date');
            $table->decimal('gross_balance', 15, 2);     // valor bruto
            $table->decimal('net_balance', 15, 2)->nullable(); // líquido de IR, se o banco mostrar
            $table->timestamps();

            $table->unique(['asset_id', 'reference_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_balance_updates');
    }
};
