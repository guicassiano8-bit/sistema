<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\TasksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('login.index')->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->name('login.destroy')->middleware('auth');
Route::post('/', [LoginController::class, 'store'])->name('login.store')->middleware('guest');

Route::view('/dashboard', 'status.index')->name('dashboard')->middleware('auth');

Route::resource('/missoes', TasksController::class)->middleware('auth');
Route::patch('/missoes/{missao}/toggle', [TasksController::class, 'toggle'])->name('missoes.toggle')->middleware('auth');
Route::patch('/missoes/{missao}/transferir', [TasksController::class, 'transferir'])->name('missoes.transferir')->middleware('auth');
