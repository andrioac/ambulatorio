<?php

namespace App\Aplicacao\Autorizacao;

use App\Models\AtribuicaoPerfil;
use App\Models\OrganizacaoSaude;
use App\Models\Perfil;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AtribuirPerfilUsuario
{
    public function __construct(private readonly AutorizadorEscopado $autorizador)
    {
    }

    public function executar(
        User $ator,
        User $usuario,
        Perfil $perfil,
        string $tipoEscopo,
        ?int $organizacaoId = null,
        ?int $unidadeId = null,
        ?string $vigenteDe = null,
        ?string $vigenteAte = null,
    ): AtribuicaoPerfil {
        [$organizacaoId, $unidadeId] = $this->validarEscopo($tipoEscopo, $organizacaoId, $unidadeId);

        if ($tipoEscopo === 'sistema' && ! $this->autorizador->possuiEscopoSistema($ator, 'usuarios.administrar')) {
            throw ValidationException::withMessages([
                'perfil_id' => 'Somente um administrador de sistema pode conceder atribuições no escopo de sistema.',
            ]);
        }

        $this->autorizador->exigir($ator, 'usuarios.administrar', $organizacaoId, $unidadeId);

        if (! $perfil->ativo) {
            throw ValidationException::withMessages(['perfil_id' => 'O perfil selecionado está inativo.']);
        }

        if ($perfil->chave === 'superadministrador_sistema'
            && ($tipoEscopo !== 'sistema' || ! $this->autorizador->possuiEscopoSistema($ator, 'usuarios.administrar'))) {
            throw ValidationException::withMessages(['perfil_id' => 'Somente um administrador de sistema pode conceder este perfil.']);
        }

        $perfil->loadMissing('permissoes');

        foreach ($perfil->permissoes as $permissao) {
            if (! $this->autorizador->permite($ator, $permissao->chave, $organizacaoId, $unidadeId)) {
                throw ValidationException::withMessages([
                    'perfil_id' => 'O perfil contém permissões que você não pode delegar neste escopo.',
                ]);
            }
        }

        $duplicada = $usuario->atribuicoesPerfil()
            ->where('perfil_id', $perfil->id)
            ->where('tipo_escopo', $tipoEscopo)
            ->where('organizacao_saude_id', $organizacaoId)
            ->where('unidade_saude_id', $unidadeId)
            ->where('ativo', true)
            ->exists();

        if ($duplicada) {
            throw ValidationException::withMessages(['perfil_id' => 'Este perfil já está atribuído ao usuário neste escopo.']);
        }

        return DB::transaction(fn () => AtribuicaoPerfil::query()->create([
            'user_id' => $usuario->id,
            'perfil_id' => $perfil->id,
            'tipo_escopo' => $tipoEscopo,
            'organizacao_saude_id' => $organizacaoId,
            'unidade_saude_id' => $unidadeId,
            'vigente_de' => $vigenteDe,
            'vigente_ate' => $vigenteAte,
            'ativo' => true,
        ]));
    }

    /** @return array{0: int|null, 1: int|null} */
    private function validarEscopo(string $tipoEscopo, ?int $organizacaoId, ?int $unidadeId): array
    {
        if ($tipoEscopo === 'sistema') {
            return [null, null];
        }

        if ($tipoEscopo === 'organizacao') {
            $organizacao = OrganizacaoSaude::query()->where('ativo', true)->find($organizacaoId);

            if (! $organizacao) {
                throw ValidationException::withMessages(['organizacao_saude_id' => 'Selecione uma organização ativa.']);
            }

            return [$organizacao->id, null];
        }

        if ($tipoEscopo === 'unidade') {
            $unidade = UnidadeSaude::query()->with('organizacao')->where('ativo', true)->find($unidadeId);

            if (! $unidade || ! $unidade->organizacao?->ativo) {
                throw ValidationException::withMessages(['unidade_saude_id' => 'Selecione uma unidade ativa de uma organização ativa.']);
            }

            return [$unidade->organizacao_saude_id, $unidade->id];
        }

        throw ValidationException::withMessages(['tipo_escopo' => 'O tipo de escopo informado é inválido.']);
    }
}
