<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Todos os seeders são idempotentes: rodar "php artisan db:seed"
     * de novo atualiza os registros em vez de duplicar.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            FinanceCategorySeeder::class,
            AssetTypeSeeder::class,
            AccountSeeder::class,
        ]);
    }
}
