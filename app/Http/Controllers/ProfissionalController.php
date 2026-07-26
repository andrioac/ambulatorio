<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Profissionais\SalvarProfissional;
use App\Aplicacao\Profissionais\SalvarVinculoProfissionalUnidade;
use App\Models\Profissional;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class ProfissionalController extends Controller
{
    public function index(Request $request): Response
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = $request->query('situacao');

        $profissionais = Profissional::query()
            ->withCount(['vinculosUnidades as vinculos_ativos_count' => fn ($query) => $query->where('ativo', true)])
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', '%'.preg_replace('/\D/', '', $busca).'%')
                    ->orWhere('cns', 'like', '%'.preg_replace('/\D/', '', $busca).'%')
                    ->orWhere('categoria', 'like', "%{$busca}%");
            }))
            ->when(in_array($situacao, ['ativos', 'inativos'], true), fn ($query) => $query->where('ativo', $situacao === 'ativos'))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Profissionais/Index', [
            'profissionais' => $profissionais,
            'filtros' => ['busca' => $busca, 'situacao' => $situacao],
        ]);
    }

    public function create(): Response
    {
        return $this->formulario();
    }

    public function store(Request $request, SalvarProfissional $salvar, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $profissional = $salvar->executar($this->dadosValidados($request));

        $auditoria->registrar('profissional.criado', [
            'entidade_tipo' => Profissional::class,
            'entidade_id' => $profissional->id,
            'dados_posteriores' => $profissional->only($this->camposAuditaveis()),
        ], $request);

        return redirect()->route('profissionais.edit', $profissional)->with('sucesso', 'Profissional cadastrado com sucesso.');
    }

    public function edit(Profissional $profissional): Response
    {
        $profissional->load(['vinculosUnidades.unidade.organizacao']);

        return $this->formulario($profissional);
    }

    public function update(Request $request, Profissional $profissional, SalvarProfissional $salvar, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $anteriores = $profissional->only($this->camposAuditaveis());
        $atualizado = $salvar->executar($this->dadosValidados($request, $profissional), $profissional);

        $auditoria->registrar('profissional.atualizado', [
            'entidade_tipo' => Profissional::class,
            'entidade_id' => $atualizado->id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => $atualizado->only($this->camposAuditaveis()),
        ], $request);

        return back()->with('sucesso', 'Profissional atualizado com sucesso.');
    }

    public function salvarVinculo(Request $request, Profissional $profissional, SalvarVinculoProfissionalUnidade $salvar, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $dados = $request->validate([
            'unidade_saude_id' => ['required', 'integer', 'exists:unidades_saude,id'],
            'vigente_de' => ['nullable', 'date'],
            'vigente_ate' => ['nullable', 'date', 'after_or_equal:vigente_de'],
        ]);

        $unidade = UnidadeSaude::query()->findOrFail($dados['unidade_saude_id']);
        $vinculo = $salvar->executar(
            $profissional,
            $unidade,
            $dados['vigente_de'] ?? null,
            $dados['vigente_ate'] ?? null,
        );

        $auditoria->registrar('profissional.vinculo_criado', [
            'entidade_tipo' => $vinculo::class,
            'entidade_id' => $vinculo->id,
            'organizacao_saude_id' => $unidade->organizacao_saude_id,
            'unidade_saude_id' => $unidade->id,
            'dados_posteriores' => [
                'profissional_id' => $profissional->id,
                'unidade_saude_id' => $unidade->id,
                'vigente_de' => $vinculo->vigente_de?->toDateString(),
                'vigente_ate' => $vinculo->vigente_ate?->toDateString(),
                'ativo' => $vinculo->ativo,
            ],
        ], $request);

        return back()->with('sucesso', 'Vínculo com unidade salvo com sucesso.');
    }

    public function removerVinculo(Request $request, Profissional $profissional, int $vinculo, RegistradorAuditoria $auditoria): RedirectResponse
    {
        $registro = $profissional->vinculosUnidades()->with('unidade')->findOrFail($vinculo);
        $anteriores = ['ativo' => $registro->ativo];
        $registro->update(['ativo' => false]);

        $auditoria->registrar('profissional.vinculo_desativado', [
            'entidade_tipo' => $registro::class,
            'entidade_id' => $registro->id,
            'organizacao_saude_id' => $registro->unidade->organizacao_saude_id,
            'unidade_saude_id' => $registro->unidade_saude_id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => ['ativo' => false],
        ], $request);

        return back()->with('sucesso', 'Vínculo desativado com sucesso.');
    }

    private function formulario(?Profissional $profissional = null): Response
    {
        return Inertia::render('Profissionais/Formulario', [
            'profissional' => $profissional,
            'usuarios' => User::query()->where('ativo', true)->orderBy('name')->get(['id', 'name', 'email']),
            'unidades' => UnidadeSaude::query()->with('organizacao:id,nome')->where('ativo', true)->orderBy('nome')->get(['id', 'organizacao_saude_id', 'nome', 'cnes']),
        ]);
    }

    private function dadosValidados(Request $request, ?Profissional $profissional = null): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('profissionais', 'user_id')->ignore($profissional?->id)],
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20', Rule::unique('profissionais', 'cpf')->ignore($profissional?->id)],
            'cns' => ['nullable', 'string', 'max:20', Rule::unique('profissionais', 'cns')->ignore($profissional?->id)],
            'categoria' => ['required', 'string', 'max:40'],
            'cbo' => ['nullable', 'string', 'max:10'],
            'conselho_tipo' => ['nullable', 'string', 'max:20'],
            'conselho_numero' => ['nullable', 'string', 'max:30'],
            'conselho_uf' => ['nullable', 'string', 'size:2'],
            'ativo' => ['boolean'],
        ]);
    }

    private function camposAuditaveis(): array
    {
        return [
            'user_id', 'nome', 'cpf', 'cns', 'categoria', 'cbo',
            'conselho_tipo', 'conselho_numero', 'conselho_uf', 'ativo',
        ];
    }
}
