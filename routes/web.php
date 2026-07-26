<?php

use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\ProfissionalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [AutenticacaoController::class, 'criar'])->name('login');
    Route::post('/entrar', [AutenticacaoController::class, 'armazenar'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', fn () => Inertia::render('Painel'))->name('inicio');
    Route::post('/sair', [AutenticacaoController::class, 'destruir'])->name('logout');

    Route::get('/profissionais', [ProfissionalController::class, 'index'])->name('profissionais.index');
    Route::get('/profissionais/novo', [ProfissionalController::class, 'create'])->name('profissionais.create');
    Route::post('/profissionais', [ProfissionalController::class, 'store'])->name('profissionais.store');
    Route::get('/profissionais/{profissional}/editar', [ProfissionalController::class, 'edit'])->name('profissionais.edit');
    Route::put('/profissionais/{profissional}', [ProfissionalController::class, 'update'])->name('profissionais.update');
    Route::post('/profissionais/{profissional}/vinculos', [ProfissionalController::class, 'salvarVinculo'])->name('profissionais.vinculos.store');
    Route::delete('/profissionais/{profissional}/vinculos/{vinculo}', [ProfissionalController::class, 'removerVinculo'])->name('profissionais.vinculos.destroy');
});
