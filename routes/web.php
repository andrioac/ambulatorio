<?php

use App\Http\Controllers\AtribuicaoPerfilController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\OrganizacaoSaudeController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\RecuperacaoSenhaController;
use App\Http\Controllers\UnidadeSaudeController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [AutenticacaoController::class, 'criar'])->name('login');
    Route::post('/entrar', [AutenticacaoController::class, 'armazenar'])->middleware('throttle:login');

    Route::get('/esqueci-minha-senha', [RecuperacaoSenhaController::class, 'solicitar'])->name('password.request');
    Route::post('/esqueci-minha-senha', [RecuperacaoSenhaController::class, 'enviarLink'])
        ->middleware('throttle:1,1')
        ->name('password.email');
    Route::get('/redefinir-senha/{token}', [RecuperacaoSenhaController::class, 'redefinir'])->name('password.reset');
    Route::post('/redefinir-senha', [RecuperacaoSenhaController::class, 'atualizar'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', fn () => Inertia::render('Painel'))->name('inicio');
    Route::post('/sair', [AutenticacaoController::class, 'destruir'])->name('logout');

    Route::middleware('permissao:organizacoes.visualizar')->group(function (): void {
        Route::get('/organizacoes', [OrganizacaoSaudeController::class, 'index'])->name('organizacoes.index');
        Route::get('/organizacoes/{organizacao}/editar', [OrganizacaoSaudeController::class, 'edit'])->name('organizacoes.edit');
    });
    Route::middleware('permissao:organizacoes.administrar')->group(function (): void {
        Route::get('/organizacoes/nova', [OrganizacaoSaudeController::class, 'create'])->name('organizacoes.create');
        Route::post('/organizacoes', [OrganizacaoSaudeController::class, 'store'])->name('organizacoes.store');
        Route::put('/organizacoes/{organizacao}', [OrganizacaoSaudeController::class, 'update'])->name('organizacoes.update');
    });

    Route::middleware('permissao:unidades.visualizar')->group(function (): void {
        Route::get('/unidades', [UnidadeSaudeController::class, 'index'])->name('unidades.index');
        Route::get('/unidades/{unidade}/editar', [UnidadeSaudeController::class, 'edit'])->name('unidades.edit');
    });
    Route::middleware('permissao:unidades.administrar')->group(function (): void {
        Route::get('/unidades/nova', [UnidadeSaudeController::class, 'create'])->name('unidades.create');
        Route::post('/unidades', [UnidadeSaudeController::class, 'store'])->name('unidades.store');
        Route::put('/unidades/{unidade}', [UnidadeSaudeController::class, 'update'])->name('unidades.update');
    });

    Route::middleware('permissao:profissionais.visualizar')->group(function (): void {
        Route::get('/profissionais', [ProfissionalController::class, 'index'])->name('profissionais.index');
        Route::get('/profissionais/{profissional}/editar', [ProfissionalController::class, 'edit'])->name('profissionais.edit');
    });
    Route::middleware('permissao:profissionais.administrar')->group(function (): void {
        Route::get('/profissionais/novo', [ProfissionalController::class, 'create'])->name('profissionais.create');
        Route::post('/profissionais', [ProfissionalController::class, 'store'])->name('profissionais.store');
        Route::put('/profissionais/{profissional}', [ProfissionalController::class, 'update'])->name('profissionais.update');
        Route::post('/profissionais/{profissional}/vinculos', [ProfissionalController::class, 'salvarVinculo'])->name('profissionais.vinculos.store');
        Route::put('/profissionais/{profissional}/vinculos/{vinculo}', [ProfissionalController::class, 'atualizarVinculo'])->name('profissionais.vinculos.update');
        Route::delete('/profissionais/{profissional}/vinculos/{vinculo}', [ProfissionalController::class, 'removerVinculo'])->name('profissionais.vinculos.destroy');
        Route::patch('/profissionais/{profissional}/vinculos/{vinculo}/reativar', [ProfissionalController::class, 'reativarVinculo'])->name('profissionais.vinculos.reativar');
    });

    Route::middleware('permissao:usuarios.visualizar')->group(function (): void {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    });
    Route::middleware('permissao:usuarios.administrar')->group(function (): void {
        Route::get('/usuarios/novo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/atribuicoes', [AtribuicaoPerfilController::class, 'store'])->name('usuarios.atribuicoes.store');
        Route::delete('/usuarios/{usuario}/atribuicoes/{atribuicao}', [AtribuicaoPerfilController::class, 'destroy'])->name('usuarios.atribuicoes.destroy');
    });

    Route::get('/perfis', [PerfilController::class, 'index'])
        ->middleware('permissao:perfis.visualizar')
        ->name('perfis.index');

    Route::get('/auditoria', [AuditoriaController::class, 'index'])
        ->middleware('permissao:auditoria.visualizar')
        ->name('auditoria.index');
});
