<?php

// app/Http/Controllers/MissaoController.php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskTransferRequest;
use App\Models\Task;
use App\Services\TasksService;
use Illuminate\Http\Request;

class TasksController extends Controller
{
    public function __construct(private TasksService $tasks) {}

    public function index(Request $request)
    {
        $visao = in_array($request->query('v'), TasksService::VISOES, true) ? $request->query('v') : 'dia';
        $data = $this->tasks->dataDaTela($request->query('data'));

        $extras = $this->tasks->dadosDaTela($visao, $data);

        return view('missoes.index', compact('visao', 'data') + $extras);
    }

    public function store(TaskStoreRequest $request)
    {
        $this->tasks->criar($request->validated());

        return back();
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
}
