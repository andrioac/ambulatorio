<?php

namespace App\Http\Controllers;

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

    public function store(Request $request, SalvarProfissional $salvar): RedirectResponse
    {
        $profissional = $salvar->executar($this->dadosValidados($request));

        return redirect()->route('profissionais.edit', $profissional)->with('sucesso', 'Profissional cadastrado com sucesso.');
    }

    public function edit(Profissional $profissional): Response
    {
        $profissional->load(['vinculosUnidades.unidade.organizacao']);

        return $this->formulario($profissional);
    }

    public function update(Request $request, Profissional $profissional, SalvarProfissional $salvar): RedirectResponse
    {
        $salvar->executar($this->dadosValidados($request, $profissional), $profissional);

        return back()->with('sucesso', 'Profissional atualizado com sucesso.');
    }

    public function salvarVinculo(Request $request, Profissional $profissional, SalvarVinculoProfissionalUnidade $salvar): RedirectResponse
    {
        $dados = $request->validate([
            'unidade_saude_id' => ['required', 'integer', 'exists:unidades_saude,id'],
            'vigente_de' => ['nullable', 'date'],
            'vigente_ate' => ['nullable', 'date', 'after_or_equal:vigente_de'],
        ]);

        $salvar->executar(
            $profissional,
            UnidadeSaude::query()->findOrFail($dados['unidade_saude_id']),
            $dados['vigente_de'] ?? null,
            $dados['vigente_ate'] ?? null,
        );

        return back()->with('sucesso', 'Vínculo com unidade salvo com sucesso.');
    }

    public function removerVinculo(Profissional $profissional, int $vinculo): RedirectResponse
    {
        $registro = $profissional->vinculosUnidades()->findOrFail($vinculo);
        $registro->update(['ativo' => false]);

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
}
