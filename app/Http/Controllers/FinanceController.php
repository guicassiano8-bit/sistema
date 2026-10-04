<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(private FinanceService $finance) {}

    public function index(Request $request): View
    {
        $aba = $this->finance->abaDaRequisicao($request->query('aba'));

        return view('tesouro.index', $this->finance->dadosDaTela(
            $aba,
            $request->query('mes'),
            $request->query('filtro'),
        ));
    }
}
