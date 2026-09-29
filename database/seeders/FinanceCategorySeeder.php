<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Categorias iniciais de ganhos e gastos. Todas podem ser editadas ou desativadas depois.
 * Rendimentos de investimentos NÃO entram aqui: eles ficam em income_entries.
 * Ícones: nomes do Heroicons (https://heroicons.com).
 */
class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            TransactionType::Income->value => [
                ['Salário', '#2F8A5B', 'banknotes'],
                ['Freelance', '#3B9C6E', 'briefcase'],
                ['Vendas', '#4DAE80', 'shopping-bag'],
                ['Reembolso', '#5FC092', 'arrow-uturn-left'],
                ['Presente recebido', '#71D2A4', 'gift'],
                ['Outros ganhos', '#83E4B6', 'plus-circle'],
            ],
            TransactionType::Expense->value => [
                ['Moradia', '#B91C1C', 'home'],
                ['Contas da casa', '#C2410C', 'bolt'],            // luz, água, internet, gás
                ['Mercado', '#D97706', 'shopping-cart'],
                ['Alimentação fora', '#CA8A04', 'cake'],
                ['Transporte', '#4D7C0F', 'truck'],
                ['Saúde', '#0F766E', 'heart'],
                ['Educação', '#0369A1', 'academic-cap'],
                ['Assinaturas', '#4338CA', 'credit-card'],
                ['Lazer', '#7E22CE', 'musical-note'],
                ['Vestuário', '#A21CAF', 'sparkles'],
                ['Cuidados pessoais', '#BE185D', 'user'],
                ['Presentes', '#9F1239', 'gift'],
                ['Impostos e taxas', '#57534E', 'receipt-percent'],
                ['Outros gastos', '#64748B', 'ellipsis-horizontal-circle'],
            ],
        ];

        $now = now();
        $rows = [];

        foreach ($categories as $type => $items) {
            foreach ($items as [$name, $color, $icon]) {
                $rows[] = [
                    'name' => $name,
                    'type' => $type,
                    'color' => $color,
                    'icon' => $icon,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Chave única: (name, type). Se você mudar a cor de uma categoria pela tela,
        // rodar o seeder de novo NÃO sobrescreve: só cria as que faltam.
        DB::table('finance_categories')->insertOrIgnore($rows);
    }
}
