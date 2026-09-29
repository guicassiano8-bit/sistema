<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            // App\Enums\PointCurrency: xp, gold. Cada conclusão de tarefa lança um par (xp + gold).
            $table->string('currency', 10)->after('type')->default('gold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
