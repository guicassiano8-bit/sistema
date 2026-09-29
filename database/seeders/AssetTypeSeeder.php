<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de ativo. is_market_traded = true indica cotação em bolsa:
 * o valor vem de quotes. Os demais usam asset_balance_updates.
 */
class AssetTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'CDB' => false,
            'LCI' => false,
            'LCA' => false,
            'Tesouro Direto' => false,
            'FII' => true,
            'Ação' => true,
            'ETF' => true,
        ];

        $now = now();

        DB::table('asset_types')->upsert(
            collect($types)->map(fn (bool $traded, string $name) => [
                'name' => $name,
                'is_market_traded' => $traded,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all(),
            uniqueBy: ['name'],
            update: ['is_market_traded', 'updated_at'],
        );
    }
}
