<?php

namespace App\Services;

use App\Enums\IncomeType;
use App\Enums\InvestmentTransactionType;
use App\Enums\QuoteSource;
use App\Models\Asset;
use App\Models\AssetBalanceUpdate;
use App\Models\AssetType;
use App\Models\IncomeEntry;
use App\Models\InvestmentTransaction;
use App\Models\PortfolioSnapshot;
use App\Models\Quote;
use App\Support\AssetPosition;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Renda fixa (CDB) vale o saldo que o banco mostra, informado à mão.
 * Ativos com cotas (FII) valem cotas × última cotação, com custo médio móvel.
 * Todo cálculo usa BigDecimal: nada de float em dinheiro.
 */
class InvestmentService
{
    /** Tipos de ativo que a tela sabe cadastrar hoje. */
    public const TYPES = ['CDB', 'FII'];

    private const RELATIONS = ['assetType', 'latestQuote', 'latestBalanceUpdate', 'transactions', 'incomeEntries'];

    /**
     * @return Collection<int, AssetPosition>
     */
    public function carteira(): Collection
    {
        return Asset::query()->active()->with(self::RELATIONS)->orderBy('name')->get()
            ->map(fn (Asset $asset) => $this->posicao($asset));
    }

    /**
     * Valor dos ativos ativos hoje (sem $ate) ou no fim de um dia passado.
     * Rendimentos recebidos não entram: não são patrimônio, só desempenho.
     */
    public function valorTotal(?CarbonInterface $ate = null): BigDecimal
    {
        $zero = BigDecimal::zero()->toScale(2);

        if ($ate === null) {
            return $this->carteira()->reduce(fn (BigDecimal $soma, AssetPosition $p) => $soma->plus($p->current), $zero);
        }

        return Asset::query()->active()->with('assetType')->get()
            ->reduce(fn (BigDecimal $soma, Asset $asset) => $soma->plus($this->valorEm($asset, $ate)), $zero);
    }

    /**
     * Grava (ou atualiza) a fotografia do mês de cada ativo ativo: quanto estava aplicado e quanto valia.
     * Mês corrente usa os valores de hoje; mês passado, os do último dia dele. Rodar de novo corrige.
     *
     * @return int quantidade de ativos fotografados
     */
    public function fotografar(CarbonInterface $mes): int
    {
        $referencia = $mes->copy()->startOfMonth();
        $data = $referencia->isSameMonth(today()) ? today() : $referencia->copy()->endOfMonth();
        $ativos = Asset::query()->active()->with('assetType')->get();

        foreach ($ativos as $asset) {
            ['invested' => $aplicado, 'value' => $valor] = $this->posicaoEm($asset, $data);

            PortfolioSnapshot::updateOrCreate(
                ['asset_id' => $asset->id, 'reference_month' => $referencia],
                ['invested_amount' => $aplicado, 'market_value' => $valor],
            );
        }

        return $ativos->count();
    }

    /** Mesma regra de posicao(), olhando só o que existia até o fim de $data. */
    private function valorEm(Asset $asset, CarbonInterface $data): BigDecimal
    {
        return $this->posicaoEm($asset, $data)['value'];
    }

    /**
     * @return array{invested: BigDecimal, value: BigDecimal}
     */
    private function posicaoEm(Asset $asset, CarbonInterface $data): array
    {
        $fim = $data->copy()->endOfDay();
        $transacoes = $this->ordenar($asset->transactions()->where('date', '<=', $fim)->get());

        if ($asset->assetType->is_market_traded) {
            ['quantity' => $quantidade, 'cost' => $custo] = $this->estado($transacoes);
            $preco = $asset->quotes()->where('date', '<=', $fim)->orderByDesc('date')->value('price');

            return [
                'invested' => $custo,
                'value' => $preco === null ? $custo : $quantidade->multipliedBy($preco)->toScale(2, RoundingMode::HalfUp),
            ];
        }

        $aplicado = BigDecimal::zero()->toScale(2);
        foreach ($transacoes as $t) {
            $aplicado = $t->type->isInflow() ? $aplicado->plus($t->total) : $aplicado->minus($t->total);
        }
        $aplicado = BigDecimal::max($aplicado, BigDecimal::zero())->toScale(2);

        $saldo = $asset->balanceUpdates()->where('reference_date', '<=', $fim)->orderByDesc('reference_date')->value('gross_balance');

        return [
            'invested' => $aplicado,
            'value' => $saldo !== null ? BigDecimal::of($saldo)->toScale(2, RoundingMode::HalfUp) : $aplicado,
        ];
    }

    /** Usa só as relações já carregadas (o modo estrito do Eloquent proíbe lazy loading). */
    public function posicao(Asset $asset): AssetPosition
    {
        $marketTraded = $asset->assetType->is_market_traded;
        $transacoes = $this->ordenar($asset->transactions);
        $rendimentos = $asset->incomeEntries->reduce(
            fn (BigDecimal $soma, IncomeEntry $e) => $soma->plus($e->amount),
            BigDecimal::zero()->toScale(2),
        );
        $ultimoRendimento = $asset->incomeEntries
            ->sortByDesc(fn (IncomeEntry $e) => $e->payment_date->format('Y-m-d').str_pad((string) $e->id, 12, '0', STR_PAD_LEFT))
            ->first();

        if ($marketTraded) {
            ['quantity' => $quantidade, 'cost' => $custo] = $this->estado($transacoes);
            $preco = $asset->latestQuote ? BigDecimal::of($asset->latestQuote->price) : null;
            // sem cotação informada, vale o que custou
            $atual = $preco ? $quantidade->multipliedBy($preco)->toScale(2, RoundingMode::HalfUp) : $custo;

            return new AssetPosition($asset, $asset->assetType->name, true, $custo, $atual, $quantidade, $preco, $ultimoRendimento, $rendimentos);
        }

        $aplicado = BigDecimal::zero()->toScale(2);
        foreach ($transacoes as $t) {
            $aplicado = $t->type->isInflow() ? $aplicado->plus($t->total) : $aplicado->minus($t->total);
        }
        $aplicado = BigDecimal::max($aplicado, BigDecimal::zero())->toScale(2);
        $atual = $asset->latestBalanceUpdate ? BigDecimal::of($asset->latestBalanceUpdate->gross_balance)->toScale(2) : $aplicado;

        return new AssetPosition($asset, $asset->assetType->name, false, $aplicado, $atual, null, null, $ultimoRendimento, $rendimentos);
    }

    /**
     * Cadastra o ativo já com o primeiro aporte (ou a primeira compra, no caso de cotas).
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Asset
    {
        return DB::transaction(function () use ($dados) {
            $tipo = AssetType::query()->where('name', $dados['type'])->firstOrFail();

            $asset = Asset::create([
                'asset_type_id' => $tipo->id,
                'name' => $dados['name'],
                'institution' => $dados['institution'] ?? null,
                'ticker' => $tipo->is_market_traded ? $dados['ticker'] : null,
                'indexer' => $tipo->is_market_traded ? null : ($dados['indexer'] ?? null),
                'rate' => $tipo->is_market_traded ? null : ($dados['rate'] ?? null),
                'maturity_date' => $tipo->is_market_traded ? null : ($dados['maturity_date'] ?? null),
                'is_active' => true,
            ]);

            if ($tipo->is_market_traded) {
                $quantidade = BigDecimal::of($dados['quantity']);
                $this->registrarMovimento($asset, [
                    'type' => InvestmentTransactionType::Buy->value,
                    'date' => $dados['date'],
                    'quantity' => $dados['quantity'],
                    'unit_price' => (string) BigDecimal::of($dados['amount'])->dividedBy($quantidade, 6, RoundingMode::HalfUp),
                    'account_id' => $dados['account_id'] ?? null,
                ]);
            } else {
                $this->registrarMovimento($asset, [
                    'type' => InvestmentTransactionType::Deposit->value,
                    'date' => $dados['date'],
                    'total' => $dados['amount'],
                    'account_id' => $dados['account_id'] ?? null,
                ]);
            }

            return $asset;
        });
    }

    /**
     * Checkbox desmarcado não é enviado: ausente significa encerrado.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Asset $asset, array $dados): Asset
    {
        $asset->loadMissing('assetType');
        $marketTraded = $asset->assetType->is_market_traded;

        $asset->update([
            'name' => $dados['name'],
            'institution' => $dados['institution'] ?? null,
            'ticker' => $marketTraded ? $dados['ticker'] : null,
            'indexer' => $marketTraded ? null : ($dados['indexer'] ?? null),
            'rate' => $marketTraded ? null : ($dados['rate'] ?? null),
            'maturity_date' => $marketTraded ? null : ($dados['maturity_date'] ?? null),
            'is_active' => (bool) ($dados['is_active'] ?? false),
        ]);

        return $asset;
    }

    /**
     * Aporte/resgate (renda fixa) ou compra/venda (cotas). Na renda fixa o saldo informado
     * acompanha o movimento, para o valor atual não ficar defasado até a próxima atualização.
     *
     * @param  array<string, mixed>  $dados
     */
    public function registrarMovimento(Asset $asset, array $dados): InvestmentTransaction
    {
        return DB::transaction(function () use ($asset, $dados) {
            $asset->load(self::RELATIONS);
            $tipo = InvestmentTransactionType::from($dados['type']);
            $data = Carbon::parse($dados['date']);
            $taxas = BigDecimal::of($dados['fees'] ?? 0)->toScale(2, RoundingMode::HalfUp);

            if ($tipo->requiresQuantity()) {
                return $this->movimentarCotas($asset, $tipo, $data, $dados, $taxas);
            }

            $total = BigDecimal::of($dados['total'])->toScale(2, RoundingMode::HalfUp);
            $atual = $this->posicao($asset)->current;

            if (! $tipo->isInflow() && $total->isGreaterThan($atual)) {
                throw ValidationException::withMessages(['total' => 'O resgate é maior que o saldo atual ('.Money::format((string) $atual).').'])->errorBag('movimento');
            }

            $movimento = $asset->transactions()->create([
                'account_id' => $dados['account_id'] ?? null,
                'type' => $tipo,
                'date' => $data,
                'total' => $total,
                'fees' => 0,
            ]);

            AssetBalanceUpdate::updateOrCreate(
                ['asset_id' => $asset->id, 'reference_date' => $data],
                ['gross_balance' => $tipo->isInflow() ? $atual->plus($total) : $atual->minus($total), 'net_balance' => null],
            );

            return $movimento;
        });
    }

    /**
     * Guarda a cotação (cotas) ou o saldo informado (renda fixa) de uma data.
     *
     * @param  array<string, mixed>  $dados  value, date e, na renda fixa, net_balance
     */
    /** A chave usa Carbon (não texto) para casar com o formato em que o Eloquent grava a data. */
    public function atualizarValor(Asset $asset, array $dados): void
    {
        $asset->loadMissing('assetType');

        if ($asset->assetType->is_market_traded) {
            Quote::updateOrCreate(
                ['asset_id' => $asset->id, 'date' => Carbon::parse($dados['date'])],
                ['price' => $dados['value'], 'source' => QuoteSource::Manual],
            );

            return;
        }

        AssetBalanceUpdate::updateOrCreate(
            ['asset_id' => $asset->id, 'reference_date' => Carbon::parse($dados['date'])],
            ['gross_balance' => $dados['value'], 'net_balance' => $dados['net_balance'] ?? null],
        );
    }

    /**
     * @param  array<string, mixed>  $dados  asset_id, amount, payment_date
     */
    public function registrarRendimento(array $dados): IncomeEntry
    {
        $asset = Asset::query()->with('assetType')->findOrFail($dados['asset_id']);
        $pagamento = Carbon::parse($dados['payment_date']);

        $rendimento = IncomeEntry::create([
            'asset_id' => $asset->id,
            'reference_month' => $pagamento->copy()->startOfMonth(),
            'payment_date' => $pagamento,
            'amount' => $dados['amount'],
            'type' => $asset->assetType->is_market_traded ? IncomeType::Dividend : IncomeType::Interest,
        ]);

        return $rendimento->setRelation('asset', $asset);
    }

    /**
     * Ativo com rendimentos ou saídas tem história: é só encerrado. Um cadastro errado
     * (só aportes/compras) pode ser apagado junto com seus movimentos.
     *
     * @return bool true se apagou, false se apenas encerrou
     */
    public function excluir(Asset $asset): bool
    {
        if ($asset->incomeEntries()->exists() || $asset->transactions()->outflows()->exists()) {
            $asset->update(['is_active' => false]);

            return false;
        }

        DB::transaction(function () use ($asset) {
            $asset->transactions()->delete();
            $asset->delete();
        });

        return true;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function movimentarCotas(Asset $asset, InvestmentTransactionType $tipo, CarbonInterface $data, array $dados, BigDecimal $taxas): InvestmentTransaction
    {
        $quantidade = BigDecimal::of($dados['quantity']);
        $preco = BigDecimal::of($dados['unit_price']);
        $total = $quantidade->multipliedBy($preco)->toScale(2, RoundingMode::HalfUp);
        $extra = [];

        if ($tipo->realizesProfit()) {
            ['quantity' => $emCarteira, 'cost' => $custo] = $this->estado($this->ordenar($asset->transactions), $data);

            if ($quantidade->isGreaterThan($emCarteira)) {
                throw ValidationException::withMessages(['quantity' => 'Você tem só '.$emCarteira->toScale(0, RoundingMode::Down).' cotas.'])->errorBag('movimento');
            }

            // o custo médio e o lucro ficam congelados nesta venda
            $custoMedio = $custo->dividedBy($emCarteira, 6, RoundingMode::HalfUp);
            $extra = [
                'average_cost' => $custoMedio,
                'realized_profit' => $total->minus($custoMedio->multipliedBy($quantidade))->minus($taxas)->toScale(2, RoundingMode::HalfUp),
            ];
        }

        return $asset->transactions()->create([
            'account_id' => $dados['account_id'] ?? null,
            'type' => $tipo,
            'date' => $data,
            'quantity' => $quantidade,
            'unit_price' => $preco,
            'total' => $total,
            'fees' => $taxas,
        ] + $extra);
    }

    /**
     * Cotas e custo que sobram depois das compras e vendas (custo médio móvel; a taxa de compra entra no custo).
     *
     * @param  Collection<int, InvestmentTransaction>  $transacoes
     * @return array{quantity: BigDecimal, cost: BigDecimal}
     */
    private function estado(Collection $transacoes, ?CarbonInterface $ate = null): array
    {
        $quantidade = BigDecimal::zero();
        $custo = BigDecimal::zero();

        foreach ($transacoes as $t) {
            if ($ate !== null && $t->date->gt($ate)) {
                continue;
            }

            if ($t->type === InvestmentTransactionType::Buy) {
                $quantidade = $quantidade->plus($t->quantity);
                $custo = $custo->plus($t->total)->plus($t->fees);
            } elseif ($t->type === InvestmentTransactionType::Sell && $quantidade->isPositive()) {
                $medio = $custo->dividedBy($quantidade, 10, RoundingMode::HalfUp);
                $custo = $custo->minus($medio->multipliedBy($t->quantity));
                $quantidade = $quantidade->minus($t->quantity);
            }
        }

        return ['quantity' => $quantidade, 'cost' => $custo->toScale(2, RoundingMode::HalfUp)];
    }

    /**
     * @param  Collection<int, InvestmentTransaction>  $transacoes
     * @return Collection<int, InvestmentTransaction>
     */
    private function ordenar(Collection $transacoes): Collection
    {
        return $transacoes->sortBy(fn (InvestmentTransaction $t) => $t->date->format('Y-m-d').str_pad((string) $t->id, 12, '0', STR_PAD_LEFT))->values();
    }
}
