<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('login.index')->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->name('login.destroy')->middleware('auth');
Route::post('/', [LoginController::class, 'store'])->name('login.store')->middleware('guest');

Route::view('/dashboard', 'status.index')->name('dashboard')->middleware('auth');
