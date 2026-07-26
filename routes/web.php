<?php

use App\Http\Controllers\AutenticacaoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [AutenticacaoController::class, 'criar'])->name('login');
    Route::post('/entrar', [AutenticacaoController::class, 'armazenar'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', fn () => Inertia::render('Home'))->name('inicio');
    Route::post('/sair', [AutenticacaoController::class, 'destruir'])->name('logout');
});
