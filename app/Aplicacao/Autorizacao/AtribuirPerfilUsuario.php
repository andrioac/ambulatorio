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
    public function __construct(private readonly AutorizadorEscopado $autorizador) {}

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
        [$organizacaoPersistidaId, $unidadePersistidaId, $organizacaoAlvoId] = $this->validarEscopo(
            $tipoEscopo,
            $organizacaoId,
            $unidadeId,
        );

        if ($tipoEscopo === 'sistema' && $this->autorizador->possuiEscopoSistema($ator, 'usuarios.administrar') === false) {
            throw ValidationException::withMessages([
                'perfil_id' => 'Somente um administrador de sistema pode conceder atribuições no escopo de sistema.',
            ]);
        }

        $this->autorizador->exigir($ator, 'usuarios.administrar', $organizacaoAlvoId, $unidadePersistidaId);

        if ($perfil->ativo === false) {
            throw ValidationException::withMessages(['perfil_id' => 'O perfil selecionado está inativo.']);
        }

        if ($perfil->chave === 'superadministrador_sistema'
            && ($tipoEscopo !== 'sistema' || $this->autorizador->possuiEscopoSistema($ator, 'usuarios.administrar') === false)) {
            throw ValidationException::withMessages(['perfil_id' => 'Somente um administrador de sistema pode conceder este perfil.']);
        }

        $perfil->loadMissing('permissoes');

        foreach ($perfil->permissoes as $permissao) {
            if ($this->autorizador->permite($ator, $permissao->chave, $organizacaoAlvoId, $unidadePersistidaId) === false) {
                throw ValidationException::withMessages([
                    'perfil_id' => 'O perfil contém permissões que você não pode delegar neste escopo.',
                ]);
            }
        }

        $duplicada = $usuario->atribuicoesPerfil()
            ->where('perfil_id', $perfil->id)
            ->where('tipo_escopo', $tipoEscopo)
            ->where('organizacao_saude_id', $organizacaoPersistidaId)
            ->where('unidade_saude_id', $unidadePersistidaId)
            ->where('ativo', true)
            ->exists();

        if ($duplicada) {
            throw ValidationException::withMessages(['perfil_id' => 'Este perfil já está atribuído ao usuário neste escopo.']);
        }

        return DB::transaction(fn () => AtribuicaoPerfil::query()->create([
            'user_id' => $usuario->id,
            'perfil_id' => $perfil->id,
            'tipo_escopo' => $tipoEscopo,
            'organizacao_saude_id' => $organizacaoPersistidaId,
            'unidade_saude_id' => $unidadePersistidaId,
            'vigente_de' => $vigenteDe,
            'vigente_ate' => $vigenteAte,
            'ativo' => true,
        ]));
    }

    /**
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function validarEscopo(string $tipoEscopo, ?int $organizacaoId, ?int $unidadeId): array
    {
        if ($tipoEscopo === 'sistema') {
            return [null, null, null];
        }

        if ($tipoEscopo === 'organizacao') {
            $organizacao = OrganizacaoSaude::query()->where('ativo', true)->find($organizacaoId);

            if ($organizacao === null) {
                throw ValidationException::withMessages(['organizacao_saude_id' => 'Selecione uma organização ativa.']);
            }

            return [$organizacao->id, null, $organizacao->id];
        }

        if ($tipoEscopo === 'unidade') {
            $unidade = UnidadeSaude::query()->with('organizacao')->where('ativo', true)->find($unidadeId);

            if ($unidade === null || $unidade->organizacao?->ativo !== true) {
                throw ValidationException::withMessages(['unidade_saude_id' => 'Selecione uma unidade ativa de uma organização ativa.']);
            }

            return [null, $unidade->id, $unidade->organizacao_saude_id];
        }

        throw ValidationException::withMessages(['tipo_escopo' => 'O tipo de escopo informado é inválido.']);
    }
}
