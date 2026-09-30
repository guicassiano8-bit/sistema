<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ouro próprio e dificuldade (rank) nas missões. O molde recorrente
 * também guarda os dois para copiá-los às ocorrências.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tasks', 'recurring_tasks'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->unsignedInteger('gold')->default(0)->after('points');
                $table->string('rank', 1)->default('E')->after('gold'); // App\Enums\TaskRank
            });

            // até aqui o ouro concedido era igual ao XP; preserva o comportamento
            DB::table($tabela)->update(['gold' => DB::raw('points')]);
        }
    }

    public function down(): void
    {
        foreach (['tasks', 'recurring_tasks'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn(['gold', 'rank']);
            });
        }
    }
};
