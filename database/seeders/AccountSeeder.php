<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cria só a "Carteira", para gastos em dinheiro vivo.
 * Contas bancárias, corretoras e cartões você cadastra pela tela,
 * com o saldo inicial real de cada uma.
 */
class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('accounts')
            ->where('type', AccountType::Wallet->value)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('accounts')->insert([
            'name' => 'Carteira',
            'type' => AccountType::Wallet->value,
            'institution' => null,
            'initial_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
