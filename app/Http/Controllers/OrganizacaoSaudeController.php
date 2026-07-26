<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\OrganizacaoSaude;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizacaoSaudeController extends Controller
{
    public function index(Request $request, AutorizadorEscopado $autorizador): Response
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = (string) $request->query('situacao');
        $permitidas = $autorizador->organizacoesPermitidas($request->user(), 'organizacoes.visualizar');

        $organizacoes = OrganizacaoSaude::query()
            ->withCount('unidades')
            ->when($permitidas !== null, fn ($query) => $query->whereKey($permitidas))
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $digitos = preg_replace('/\D/', '', $busca);
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('sigla', 'like', "%{$busca}%")
                    ->when($digitos !== '', fn ($query) => $query->orWhere('cnpj', 'like', "%{$digitos}%"));
            }))
            ->when(in_array($situacao, ['ativos', 'inativos'], true), fn ($query) => $query->where('ativo', $situacao === 'ativos'))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Organizacoes/Index', [
            'organizacoes' => $organizacoes,
            'filtros' => ['busca' => $busca, 'situacao' => $situacao],
            'podeAdministrar' => $autorizador->possuiEscopoSistema($request->user(), 'organizacoes.administrar'),
        ]);
    }

    public function create(Request $request, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($autorizador->possuiEscopoSistema($request->user(), 'organizacoes.administrar'), 403);

        return Inertia::render('Organizacoes/Formulario', ['organizacao' => null]);
    }

    public function store(Request $request, AutorizadorEscopado $autorizador, RegistradorAuditoria $auditoria): RedirectResponse
    {
        abort_unless($autorizador->possuiEscopoSistema($request->user(), 'organizacoes.administrar'), 403);

        $dados = $this->dadosValidados($request);
        $organizacao = OrganizacaoSaude::query()->create($dados);

        $auditoria->registrar('organizacao.criada', [
            'entidade_tipo' => OrganizacaoSaude::class,
            'entidade_id' => $organizacao->id,
            'organizacao_saude_id' => $organizacao->id,
            'dados_posteriores' => $organizacao->only(['nome', 'sigla', 'cnpj', 'ativo']),
        ], $request);

        return redirect()->route('organizacoes.edit', $organizacao)->with('sucesso', 'Organização cadastrada com sucesso.');
    }

    public function edit(Request $request, OrganizacaoSaude $organizacao, AutorizadorEscopado $autorizador): Response
    {
        $autorizador->exigir($request->user(), 'organizacoes.visualizar', $organizacao->id);

        return Inertia::render('Organizacoes/Formulario', [
            'organizacao' => $organizacao,
            'podeAdministrar' => $autorizador->permite($request->user(), 'organizacoes.administrar', $organizacao->id),
        ]);
    }

    public function update(Request $request, OrganizacaoSaude $organizacao, AutorizadorEscopado $autorizador, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $autorizador->exigir($request->user(), 'organizacoes.administrar', $organizacao->id);
        $anteriores = $organizacao->only(['nome', 'sigla', 'cnpj', 'ativo']);
        $organizacao->update($this->dadosValidados($request, $organizacao));

        $auditoria->registrar('organizacao.atualizada', [
            'entidade_tipo' => OrganizacaoSaude::class,
            'entidade_id' => $organizacao->id,
            'organizacao_saude_id' => $organizacao->id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => $organizacao->fresh()->only(['nome', 'sigla', 'cnpj', 'ativo']),
        ], $request);

        return back()->with('sucesso', 'Organização atualizada com sucesso.');
    }

    private function dadosValidados(Request $request, ?OrganizacaoSaude $organizacao = null): array
    {
        $request->merge([
            'cnpj' => ($cnpj = preg_replace('/\D/', '', (string) $request->input('cnpj'))) !== '' ? $cnpj : null,
            'sigla' => ($sigla = trim((string) $request->input('sigla'))) !== '' ? mb_strtoupper($sigla) : null,
        ]);

        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => ['nullable', 'string', 'max:20'],
            'cnpj' => ['nullable', 'digits:14', Rule::unique('organizacoes_saude', 'cnpj')->ignore($organizacao?->id)],
            'ativo' => ['boolean'],
        ]);
    }
}
