<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\OrganizacaoSaude;
use App\Models\UnidadeSaude;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class UnidadeSaudeController extends Controller
{
    public function index(Request $request, AutorizadorEscopado $autorizador): Response
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = (string) $request->query('situacao');
        $organizacaoId = $request->integer('organizacao_id') ?: null;
        $permitidas = $autorizador->unidadesPermitidas($request->user(), 'unidades.visualizar');

        $unidades = UnidadeSaude::query()
            ->with('organizacao:id,nome')
            ->when($permitidas !== null, fn ($query) => $query->whereKey($permitidas))
            ->when($organizacaoId, fn ($query) => $query->where('organizacao_saude_id', $organizacaoId))
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $digitos = preg_replace('/\D/', '', $busca);
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('tipo', 'like', "%{$busca}%")
                    ->when($digitos !== '', fn ($query) => $query->orWhere('cnes', 'like', "%{$digitos}%"));
            }))
            ->when(in_array($situacao, ['ativos', 'inativos'], true), fn ($query) => $query->where('ativo', $situacao === 'ativos'))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Unidades/Index', [
            'unidades' => $unidades,
            'organizacoes' => $this->organizacoesVisiveis($request, $autorizador, 'unidades.visualizar'),
            'filtros' => ['busca' => $busca, 'situacao' => $situacao, 'organizacao_id' => $organizacaoId],
            'podeAdministrar' => $autorizador->possuiPermissao($request->user(), 'unidades.administrar'),
        ]);
    }

    public function create(Request $request, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($autorizador->possuiPermissao($request->user(), 'unidades.administrar'), 403);

        return Inertia::render('Unidades/Formulario', [
            'unidade' => null,
            'organizacoes' => $this->organizacoesVisiveis($request, $autorizador, 'unidades.administrar', somenteAtivas: true),
        ]);
    }

    public function store(Request $request, AutorizadorEscopado $autorizador, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $dados = $this->dadosValidados($request);
        $organizacao = OrganizacaoSaude::query()->findOrFail($dados['organizacao_saude_id']);
        $autorizador->exigir($request->user(), 'unidades.administrar', $organizacao->id);
        $this->validarOrganizacaoAtiva($organizacao, (bool) $dados['ativo']);

        $unidade = UnidadeSaude::query()->create($dados);

        $auditoria->registrar('unidade.criada', [
            'entidade_tipo' => UnidadeSaude::class,
            'entidade_id' => $unidade->id,
            'organizacao_saude_id' => $organizacao->id,
            'unidade_saude_id' => $unidade->id,
            'dados_posteriores' => $unidade->only(['organizacao_saude_id', 'nome', 'cnes', 'tipo', 'ativo']),
        ], $request);

        return redirect()->route('unidades.edit', $unidade)->with('sucesso', 'Unidade cadastrada com sucesso.');
    }

    public function edit(Request $request, UnidadeSaude $unidade, AutorizadorEscopado $autorizador): Response
    {
        $autorizador->exigir($request->user(), 'unidades.visualizar', $unidade->organizacao_saude_id, $unidade->id);

        return Inertia::render('Unidades/Formulario', [
            'unidade' => $unidade->load('organizacao:id,nome'),
            'organizacoes' => $this->organizacoesVisiveis($request, $autorizador, 'unidades.administrar'),
            'podeAdministrar' => $autorizador->permite($request->user(), 'unidades.administrar', $unidade->organizacao_saude_id, $unidade->id),
        ]);
    }

    public function update(Request $request, UnidadeSaude $unidade, AutorizadorEscopado $autorizador, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $autorizador->exigir($request->user(), 'unidades.administrar', $unidade->organizacao_saude_id, $unidade->id);
        $dados = $this->dadosValidados($request, $unidade);
        $organizacao = OrganizacaoSaude::query()->findOrFail($dados['organizacao_saude_id']);

        if ($organizacao->id !== $unidade->organizacao_saude_id) {
            $autorizador->exigir($request->user(), 'unidades.administrar', $organizacao->id);
        }

        $this->validarOrganizacaoAtiva($organizacao, (bool) $dados['ativo']);
        $anteriores = $unidade->only(['organizacao_saude_id', 'nome', 'cnes', 'tipo', 'ativo']);
        $unidade->update($dados);

        $auditoria->registrar('unidade.atualizada', [
            'entidade_tipo' => UnidadeSaude::class,
            'entidade_id' => $unidade->id,
            'organizacao_saude_id' => $organizacao->id,
            'unidade_saude_id' => $unidade->id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => $unidade->fresh()->only(['organizacao_saude_id', 'nome', 'cnes', 'tipo', 'ativo']),
        ], $request);

        return back()->with('sucesso', 'Unidade atualizada com sucesso.');
    }

    private function dadosValidados(Request $request, ?UnidadeSaude $unidade = null): array
    {
        $request->merge([
            'cnes' => ($cnes = preg_replace('/\D/', '', (string) $request->input('cnes'))) !== '' ? $cnes : null,
            'tipo' => mb_strtolower(trim((string) $request->input('tipo'))),
        ]);

        return $request->validate([
            'organizacao_saude_id' => ['required', 'integer', 'exists:organizacoes_saude,id'],
            'nome' => ['required', 'string', 'max:255'],
            'cnes' => ['nullable', 'digits:7', Rule::unique('unidades_saude', 'cnes')->ignore($unidade?->id)],
            'tipo' => ['required', 'string', 'max:50'],
            'ativo' => ['boolean'],
        ]);
    }

    private function organizacoesVisiveis(Request $request, AutorizadorEscopado $autorizador, string $permissao, bool $somenteAtivas = false)
    {
        $permitidas = $autorizador->organizacoesPermitidas($request->user(), $permissao);

        return OrganizacaoSaude::query()
            ->when($permitidas !== null, fn ($query) => $query->whereKey($permitidas))
            ->when($somenteAtivas, fn ($query) => $query->where('ativo', true))
            ->orderBy('nome')
            ->get(['id', 'nome', 'ativo']);
    }

    private function validarOrganizacaoAtiva(OrganizacaoSaude $organizacao, bool $unidadeAtiva): void
    {
        if ($unidadeAtiva && ! $organizacao->ativo) {
            throw ValidationException::withMessages([
                'organizacao_saude_id' => 'Uma unidade ativa deve pertencer a uma organização ativa.',
            ]);
        }
    }
}
