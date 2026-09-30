<?php

namespace App\Http\Controllers;

use App\Services\StatusService;
use Illuminate\Contracts\View\View;

class StatusController extends Controller
{
    public function __invoke(StatusService $status): View
    {
        return view('status.index', $status->dadosDaTela());
    }
}
