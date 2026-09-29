<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movimentação entre contas. Inclui o pagamento da fatura do cartão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description')->nullable();   // ex.: Pagamento fatura setembro
            $table->timestamps();

            $table->index('date');
        });

        // Garante no banco que origem e destino são diferentes (MySQL 8.0.16+).
        // A mesma regra também deve existir no Form Request.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transfers ADD CONSTRAINT transfers_distinct_accounts CHECK (from_account_id <> to_account_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
