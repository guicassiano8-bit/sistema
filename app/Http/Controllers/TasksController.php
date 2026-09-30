<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskTransferRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use App\Services\TasksService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    public function __construct(private TasksService $tasks) {}

    public function index(TaskFilterRequest $request)
    {
        $visao = in_array($request->query('v'), TasksService::VISOES, true) ? $request->query('v') : 'dia';
        $data = $this->tasks->dataDaTela($request->query('data'));
        $filtros = $request->validated();

        $extras = $this->tasks->dadosDaTela($visao, $data, $filtros);

        return view('missoes.index', compact('visao', 'data', 'filtros') + $extras);
    }

    public function create(): View
    {
        return view('missoes.form', ['missao' => new Task]);
    }

    public function store(TaskStoreRequest $request): RedirectResponse
    {
        $missao = $this->tasks->criar($request->validated());

        // o form completo é uma página própria: volta para o dia da missão. O modal só recarrega a tela.
        if ($request->isCompleto()) {
            return $this->voltarParaOdia($missao, 'Missão criada');
        }

        return back();
    }

    public function edit(Task $missao): View
    {
        return view('missoes.form', ['missao' => $missao->load('recurringTask')]);
    }

    public function update(TaskUpdateRequest $request, Task $missao): RedirectResponse
    {
        $dados = $request->validated();

        $missao = $this->tasks->atualizar($missao, $dados, (bool) ($dados['aplicar_futuras'] ?? false));

        return $this->voltarParaOdia($missao, 'Missão atualizada');
    }

    public function destroy(Request $request, Task $missao): RedirectResponse
    {
        $data = $missao->scheduled_date->toDateString();

        $this->tasks->excluir($missao, $request->user());

        return redirect()->route('missoes.index', ['data' => $data])
            ->with('sys_toast', ['type' => 'info', 'title' => 'Missão excluída']);
    }

    public function toggle(Request $request, Task $missao)
    {
        $resultado = $this->tasks->alternarConclusao($missao, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'done' => $resultado['done'],
                'player' => $resultado['jogador']->toArray() + ['rank_changed' => $resultado['rank_changed']],
                'leveled_up' => $resultado['leveled_up'],
                'toast' => $resultado['done'] ? [
                    'type' => 'success',
                    'title' => 'Missão concluída',
                    'message' => $missao->title,
                    'value' => "+{$missao->points} XP",
                ] : null,
            ]);
        }

        return back();
    }

    public function transferir(TaskTransferRequest $request, Task $missao)
    {
        $this->tasks->transferir($missao, $request->validated()['data']);

        return back();
    }

    public function cancelar(Request $request, Task $missao): RedirectResponse
    {
        $this->tasks->cancelar($missao, $request->user());

        return redirect()->route('missoes.index', ['data' => $missao->scheduled_date->toDateString()])
            ->with('sys_toast', ['type' => 'info', 'title' => 'Missão cancelada', 'message' => $missao->title]);
    }

    public function atrasadasParaHoje(): RedirectResponse
    {
        $movidas = $this->tasks->trazerAtrasadasParaHoje();

        return redirect()->route('missoes.index')
            ->with('sys_toast', ['type' => 'info', 'title' => $movidas === 1 ? '1 missão trazida para hoje' : "{$movidas} missões trazidas para hoje"]);
    }

    public function encerrarSerie(Task $missao): RedirectResponse
    {
        $this->tasks->encerrarSerie($missao);

        return redirect()->route('missoes.index')
            ->with('sys_toast', ['type' => 'info', 'title' => 'Série encerrada', 'message' => $missao->title]);
    }

    private function voltarParaOdia(Task $missao, string $titulo): RedirectResponse
    {
        return redirect()->route('missoes.index', ['data' => $missao->scheduled_date->toDateString()])
            ->with('sys_toast', ['type' => 'success', 'title' => $titulo, 'message' => $missao->title]);
    }
}
