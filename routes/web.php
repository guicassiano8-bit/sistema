<?php

use App\Http\Controllers\LoginController;
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
