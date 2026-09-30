<?php

namespace App\Services;

use App\Enums\PointCurrency;
use App\Enums\PointTransactionType;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardService
{
    /**
     * Recompensas ativas ainda resgatáveis: as que o saldo cobre primeiro
     * (mais baratas antes), depois as bloqueadas (mais perto de conseguir antes).
     *
     * @return SupportCollection<int, Reward>
     */
    public function listar(int $saldo): SupportCollection
    {
        return Reward::query()
            ->active()
            ->withExists('redemptions')
            ->get()
            ->reject(fn (Reward $r) => ! $r->is_repeatable && $r->redemptions_exists)
            ->sortBy([
                fn (Reward $a, Reward $b) => $b->isAffordableWith($saldo) <=> $a->isAffordableWith($saldo),
                fn (Reward $a, Reward $b) => $a->cost <=> $b->cost,
            ])
            ->values();
    }

    /**
     * Resgates agrupados por mês, do mais recente ao mais antigo.
     *
     * @return SupportCollection<int, array{mes: Carbon, total: int, trocas: Collection}>
     */
    public function historico(): SupportCollection
    {
        return RewardRedemption::query()
            ->with('reward')
            ->latest('redeemed_at')
            ->get()
            ->groupBy(fn (RewardRedemption $t) => $t->redeemed_at->format('Y-m'))
            ->map(fn ($trocas) => [
                'mes' => $trocas->first()->redeemed_at->copy()->startOfMonth(),
                'total' => (int) $trocas->sum('cost_paid'),
                'trocas' => $trocas,
            ])
            ->values();
    }

    /**
     * Troca ouro por recompensa. Tudo ou nada: o saldo é relido com lock dentro da
     * transação, para dois cliques simultâneos não gastarem o mesmo ouro duas vezes.
     */
    public function resgatar(Reward $recompensa, User $user): RewardRedemption
    {
        return DB::transaction(function () use ($recompensa, $user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $recompensa = Reward::query()->lockForUpdate()->findOrFail($recompensa->id);

            if (! $recompensa->isAvailable()) {
                throw ValidationException::withMessages(['recompensa' => 'Esta recompensa não está disponível.']);
            }

            if (! $recompensa->isAffordableWith($user->ouro)) {
                throw ValidationException::withMessages(['recompensa' => 'Ouro insuficiente.']);
            }

            // o custo é congelado: mudar o preço depois não reescreve o histórico
            $resgate = $recompensa->redemptions()->create([
                'cost_paid' => $recompensa->cost,
                'redeemed_at' => now(),
            ]);

            $resgate->pointTransaction()->create([
                'amount' => -$recompensa->cost,
                'type' => PointTransactionType::RewardRedeemed,
                'currency' => PointCurrency::Gold,
            ]);

            $user->ouro -= $recompensa->cost;
            $user->save();

            return $resgate;
        });
    }

    /**
     * Desfaz um resgate: devolve o ouro pago (custo congelado) e apaga o resgate e seu lançamento.
     * Diferente do estorno de missões, aqui nada fica no histórico, por pedido do dono.
     */
    public function desfazerResgate(RewardRedemption $resgate, User $user): void
    {
        DB::transaction(function () use ($resgate, $user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            $user->ouro += $resgate->cost_paid;
            $user->save();

            $resgate->pointTransaction()->delete();
            $resgate->delete();
        });
    }

    /** @param  array<string, mixed>  $dados */
    public function criar(array $dados): Reward
    {
        return Reward::create($this->campos($dados));
    }

    /** @param  array<string, mixed>  $dados */
    public function atualizar(Reward $recompensa, array $dados): Reward
    {
        $recompensa->update($this->campos($dados) + ['is_active' => (bool) ($dados['is_active'] ?? false)]);

        return $recompensa;
    }

    /** Apaga se nunca foi resgatada; senão desativa, para preservar o histórico. Devolve true se apagou. */
    public function excluir(Reward $recompensa): bool
    {
        if ($recompensa->redemptions()->exists()) {
            $recompensa->update(['is_active' => false]);

            return false;
        }

        $recompensa->delete();

        return true;
    }

    /** Checkbox desmarcado não é enviado: ausente significa false. */
    private function campos(array $dados): array
    {
        return [
            'name' => $dados['name'],
            'description' => $dados['description'] ?? null,
            'cost' => $dados['cost'],
            'rank' => $dados['rank'],
            'is_repeatable' => (bool) ($dados['is_repeatable'] ?? false),
        ];
    }
}
