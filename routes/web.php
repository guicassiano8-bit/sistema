<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\ShoppingController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TasksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('login.index')->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->name('login.destroy')->middleware('auth');
Route::post('/', [LoginController::class, 'store'])->name('login.store')->middleware('guest');

Route::get('/dashboard', StatusController::class)->name('dashboard')->middleware('auth');

// declarada antes do resource: "atrasadas" não pode ser lido como {missao}
Route::patch('/missoes/atrasadas/hoje', [TasksController::class, 'atrasadasParaHoje'])->name('missoes.atrasadas-hoje')->middleware('auth');
Route::resource('/missoes', TasksController::class)->except('show')->parameters(['missoes' => 'missao'])->middleware('auth');
Route::patch('/missoes/{missao}/cancelar', [TasksController::class, 'cancelar'])->name('missoes.cancelar')->middleware('auth');
Route::delete('/missoes/{missao}/serie', [TasksController::class, 'encerrarSerie'])->name('missoes.encerrar-serie')->middleware('auth');
Route::patch('/missoes/{missao}/toggle', [TasksController::class, 'toggle'])->name('missoes.toggle')->middleware('auth');
Route::patch('/missoes/{missao}/transferir', [TasksController::class, 'transferir'])->name('missoes.transferir')->middleware('auth');

// declarada antes do resource: "resgates" não pode ser lido como {recompensa}
Route::delete('/recompensas/resgates/{resgate}', [RewardController::class, 'desfazer'])->name('recompensas.desfazer')->middleware('auth');
Route::resource('/recompensas', RewardController::class)->except(['create', 'show'])->parameters(['recompensas' => 'recompensa'])->middleware('auth');
Route::post('/recompensas/{recompensa}/trocar', [RewardController::class, 'trocar'])->name('recompensas.trocar')->middleware('auth');

// declarada antes do resource: "comprados" não pode ser lido como {item}
Route::delete('/inventario/comprados', [ShoppingController::class, 'limpar'])->name('inventario.limpar')->middleware('auth');
Route::resource('/inventario', ShoppingController::class)->except(['create', 'show'])->parameters(['inventario' => 'item'])->middleware('auth');
Route::patch('/inventario/{item}/toggle', [ShoppingController::class, 'toggle'])->name('inventario.toggle')->middleware('auth');
