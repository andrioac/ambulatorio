<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Autorizacao\AtribuirPerfilUsuario;
use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\AtribuicaoPerfil;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AtribuicaoPerfilController extends Controller
{
    public function store(
        Request $request,
        User $usuario,
        AtribuirPerfilUsuario $atribuir,
        RegistradorAuditoria $auditoria,
    ): RedirectResponse {
        $dados = $request->validate([
            'perfil_id' => ['required', 'integer', 'exists:perfis,id'],
            'tipo_escopo' => ['required', Rule::in(['sistema', 'organizacao', 'unidade'])],
            'organizacao_saude_id' => ['nullable', 'integer', 'exists:organizacoes_saude,id'],
            'unidade_saude_id' => ['nullable', 'integer', 'exists:unidades_saude,id'],
            'vigente_de' => ['nullable', 'date'],
            'vigente_ate' => ['nullable', 'date', 'after_or_equal:vigente_de'],
        ]);

        $atribuicao = $atribuir->executar(
            $request->user(),
            $usuario,
            Perfil::query()->findOrFail($dados['perfil_id']),
            $dados['tipo_escopo'],
            $dados['organizacao_saude_id'] ?? null,
            $dados['unidade_saude_id'] ?? null,
            $dados['vigente_de'] ?? null,
            $dados['vigente_ate'] ?? null,
        );

        $auditoria->registrar('usuario.perfil_atribuido', [
            'entidade_tipo' => AtribuicaoPerfil::class,
            'entidade_id' => $atribuicao->id,
            'organizacao_saude_id' => $atribuicao->organizacao_saude_id,
            'unidade_saude_id' => $atribuicao->unidade_saude_id,
            'dados_posteriores' => [
                'user_id' => $usuario->id,
                'perfil_id' => $atribuicao->perfil_id,
                'tipo_escopo' => $atribuicao->tipo_escopo,
                'vigente_de' => $atribuicao->vigente_de,
                'vigente_ate' => $atribuicao->vigente_ate,
                'ativo' => true,
            ],
        ], $request);

        return back()->with('sucesso', 'Perfil atribuído com sucesso.');
    }

    public function destroy(
        Request $request,
        User $usuario,
        int $atribuicao,
        AutorizadorEscopado $autorizador,
        RegistradorAuditoria $auditoria,
    ): RedirectResponse {
        $registro = $usuario->atribuicoesPerfil()->with('perfil')->findOrFail($atribuicao);
        $autorizador->exigir(
            $request->user(),
            'usuarios.administrar',
            $registro->organizacao_saude_id,
            $registro->unidade_saude_id,
        );

        if ($registro->perfil?->chave === 'superadministrador_sistema') {
            if ($usuario->is($request->user())) {
                throw ValidationException::withMessages([
                    'atribuicao' => 'Você não pode revogar o próprio acesso de superadministrador.',
                ]);
            }

            $quantidade = AtribuicaoPerfil::query()
                ->where('ativo', true)
                ->whereHas('perfil', fn ($query) => $query->where('chave', 'superadministrador_sistema'))
                ->count();

            if ($quantidade <= 1) {
                throw ValidationException::withMessages([
                    'atribuicao' => 'O sistema deve manter ao menos um superadministrador ativo.',
                ]);
            }
        }

        $registro->update(['ativo' => false]);

        $auditoria->registrar('usuario.perfil_revogado', [
            'entidade_tipo' => AtribuicaoPerfil::class,
            'entidade_id' => $registro->id,
            'organizacao_saude_id' => $registro->organizacao_saude_id,
            'unidade_saude_id' => $registro->unidade_saude_id,
            'dados_anteriores' => ['ativo' => true],
            'dados_posteriores' => ['ativo' => false],
        ], $request);

        return back()->with('sucesso', 'Atribuição revogada com sucesso.');
    }
}
