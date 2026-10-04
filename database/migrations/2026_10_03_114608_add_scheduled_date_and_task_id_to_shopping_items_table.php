<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_items', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('unit')->index();
            // a missão "Fazer Compras" do dia; se for apagada, o item só perde o vínculo
            $table->foreignId('task_id')->nullable()->after('scheduled_date')->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shopping_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropColumn('scheduled_date');
        });
    }
};
