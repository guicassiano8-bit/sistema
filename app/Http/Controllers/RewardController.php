<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardStoreRequest;
use App\Http\Requests\RewardUpdateRequest;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Services\RewardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function __construct(private RewardService $rewards) {}

    public function index(Request $request): View
    {
        $aba = $request->query('aba') === 'historico' ? 'historico' : 'loja';
        $jogador = $request->user()->jogador();

        return view('loja.index', [
            'aba' => $aba,
            'jogador' => $jogador,
            'recompensas' => $aba === 'loja' ? $this->rewards->listar($jogador->ouro) : collect(),
            'historico' => $aba === 'historico' ? $this->rewards->historico() : collect(),
        ]);
    }

    public function store(RewardStoreRequest $request): RedirectResponse
    {
        $recompensa = $this->rewards->criar($request->validated());

        return redirect()->route('recompensas.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Recompensa cadastrada', 'message' => $recompensa->name]);
    }

    public function edit(Reward $recompensa): View
    {
        return view('loja.form', ['recompensa' => $recompensa]);
    }

    public function update(RewardUpdateRequest $request, Reward $recompensa): RedirectResponse
    {
        $this->rewards->atualizar($recompensa, $request->validated());

        return redirect()->route('recompensas.index')
            ->with('sys_toast', ['type' => 'success', 'title' => 'Recompensa atualizada', 'message' => $recompensa->name]);
    }

    public function destroy(Reward $recompensa): RedirectResponse
    {
        $apagada = $this->rewards->excluir($recompensa);

        return redirect()->route('recompensas.index')
            ->with('sys_toast', ['type' => 'info', 'title' => $apagada ? 'Recompensa excluída' : 'Recompensa desativada', 'message' => $recompensa->name]);
    }

    public function desfazer(Request $request, RewardRedemption $resgate): RedirectResponse
    {
        $ouroAnterior = $request->user()->ouro;
        $nome = $resgate->reward->name;
        $valor = $resgate->cost_paid;

        $this->rewards->desfazerResgate($resgate, $request->user());

        return redirect()->route('recompensas.index', ['aba' => 'historico'])
            ->with('ouro_anterior', $ouroAnterior)
            ->with('sys_toast', [
                'type' => 'info',
                'title' => 'Resgate desfeito',
                'message' => $nome,
                'value' => '+'.number_format($valor, 0, ',', '.').' Ouro',
            ]);
    }

    public function trocar(Request $request, Reward $recompensa): RedirectResponse
    {
        $ouroAnterior = $request->user()->ouro;

        $resgate = $this->rewards->resgatar($recompensa, $request->user());

        return redirect()->route('recompensas.index')
            ->with('ouro_anterior', $ouroAnterior)
            ->with('sys_toast', [
                'type' => 'success',
                'title' => 'Recompensa resgatada',
                'message' => $recompensa->name,
                'value' => '−'.number_format($resgate->cost_paid, 0, ',', '.').' Ouro',
            ]);
    }
}
