<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Aplicacao\Profissionais\SalvarProfissional;
use App\Aplicacao\Profissionais\SalvarVinculoProfissionalUnidade;
use App\Models\Profissional;
use App\Models\UnidadeSaude;
use App\Models\User;
use App\Models\VinculoProfissionalUnidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProfissionalController extends Controller
{
    public function index(Request $request, AutorizadorEscopado $autorizador): Response
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = $request->query('situacao');
        $unidades = $autorizador->unidadesPermitidas($request->user(), 'profissionais.visualizar');

        $profissionais = Profissional::query()
            ->when($unidades !== null, fn ($query) => $query->whereHas('vinculosUnidades', fn ($query) => $query->whereIn('unidade_saude_id', $unidades)))
            ->withCount(['vinculosUnidades as vinculos_ativos_count' => fn ($query) => $query
                ->where('ativo', true)
                ->when($unidades !== null, fn ($query) => $query->whereIn('unidade_saude_id', $unidades))])
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $digitos = preg_replace('/\D/', '', $busca);
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('categoria', 'like', "%{$busca}%")
                    ->when($digitos !== '', fn ($query) => $query
                        ->orWhere('cpf', 'like', "%{$digitos}%")
                        ->orWhere('cns', 'like', "%{$digitos}%"));
            }))
            ->when(in_array($situacao, ['ativos', 'inativos'], true), fn ($query) => $query->where('ativo', $situacao === 'ativos'))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Profissionais/Index', [
            'profissionais' => $profissionais,
            'filtros' => ['busca' => $busca, 'situacao' => $situacao],
            'podeAdministrar' => $autorizador->possuiPermissao($request->user(), 'profissionais.administrar'),
        ]);
    }

    public function create(Request $request, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($autorizador->possuiPermissao($request->user(), 'profissionais.administrar'), 403);

        return $this->formulario($request, $autorizador);
    }

    public function store(
        Request $request,
        SalvarProfissional $salvar,
        SalvarVinculoProfissionalUnidade $salvarVinculo,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($autorizador->possuiPermissao($request->user(), 'profissionais.administrar'), 403);
        $sistema = $autorizador->possuiEscopoSistema($request->user(), 'profissionais.administrar');
        $dados = $this->dadosValidados($request, unidadeInicialObrigatoria: ! $sistema);
        $unidade = $this->resolverUnidadeAtiva($dados['unidade_saude_id_inicial'] ?? null);

        if ($unidade) {
            $autorizador->exigir($request->user(), 'profissionais.administrar', $unidade->organizacao_saude_id, $unidade->id);
        }

        [$profissional, $vinculo] = DB::transaction(function () use ($dados, $salvar, $salvarVinculo, $unidade): array {
            $profissional = $salvar->executar(collect($dados)->except('unidade_saude_id_inicial')->all());
            $vinculo = $unidade ? $salvarVinculo->executar($profissional, $unidade) : null;

            return [$profissional, $vinculo];
        });

        $auditoria->registrar('profissional.criado', [
            'entidade_tipo' => Profissional::class,
            'entidade_id' => $profissional->id,
            'dados_posteriores' => $profissional->only($this->camposAuditaveis()),
        ], $request);

        if ($vinculo && $unidade) {
            $this->auditarVinculo($request, $auditoria, 'profissional.vinculo_criado', $profissional, $vinculo, $unidade);
        }

        return redirect()->route('profissionais.edit', $profissional)->with('sucesso', 'Profissional cadastrado com sucesso.');
    }

    public function edit(Request $request, Profissional $profissional, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.visualizar', $autorizador), 403);
        $profissional->load(['vinculosUnidades.unidade.organizacao']);

        return $this->formulario($request, $autorizador, $profissional);
    }

    public function update(
        Request $request,
        Profissional $profissional,
        SalvarProfissional $salvar,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador), 403);
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

    public function salvarVinculo(
        Request $request,
        Profissional $profissional,
        SalvarVinculoProfissionalUnidade $salvar,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador), 403);
        $dados = $this->dadosVinculoValidados($request);
        $unidade = $this->resolverUnidadeAtiva($dados['unidade_saude_id']);
        $autorizador->exigir($request->user(), 'profissionais.administrar', $unidade->organizacao_saude_id, $unidade->id);

        $vinculo = $salvar->executar($profissional, $unidade, $dados['vigente_de'] ?? null, $dados['vigente_ate'] ?? null);
        $this->auditarVinculo($request, $auditoria, 'profissional.vinculo_criado', $profissional, $vinculo, $unidade);

        return back()->with('sucesso', 'Vínculo com unidade salvo com sucesso.');
    }

    public function atualizarVinculo(
        Request $request,
        Profissional $profissional,
        int $vinculo,
        SalvarVinculoProfissionalUnidade $salvar,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador), 403);
        $registro = $profissional->vinculosUnidades()->with('unidade.organizacao')->findOrFail($vinculo);
        $autorizador->exigir($request->user(), 'profissionais.administrar', $registro->unidade->organizacao_saude_id, $registro->unidade_id);
        $dados = $this->dadosVinculoValidados($request);
        $unidade = $this->resolverUnidadeAtiva($dados['unidade_saude_id']);
        $autorizador->exigir($request->user(), 'profissionais.administrar', $unidade->organizacao_saude_id, $unidade->id);

        $anteriores = $this->dadosAuditaveisVinculo($registro);
        $atualizado = $salvar->executar(
            $profissional,
            $unidade,
            $dados['vigente_de'] ?? null,
            $dados['vigente_ate'] ?? null,
            $registro->ativo,
            $registro,
        );

        $auditoria->registrar('profissional.vinculo_atualizado', [
            'entidade_tipo' => VinculoProfissionalUnidade::class,
            'entidade_id' => $atualizado->id,
            'organizacao_saude_id' => $unidade->organizacao_saude_id,
            'unidade_saude_id' => $unidade->id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => $this->dadosAuditaveisVinculo($atualizado),
        ], $request);

        return back()->with('sucesso', 'Vínculo atualizado com sucesso.');
    }

    public function removerVinculo(
        Request $request,
        Profissional $profissional,
        int $vinculo,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador), 403);
        $registro = $profissional->vinculosUnidades()->with('unidade')->findOrFail($vinculo);
        $autorizador->exigir($request->user(), 'profissionais.administrar', $registro->unidade->organizacao_saude_id, $registro->unidade_saude_id);
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

    public function reativarVinculo(
        Request $request,
        Profissional $profissional,
        int $vinculo,
        RegistradorAuditoria $auditoria,
        AutorizadorEscopado $autorizador,
    ): RedirectResponse {
        abort_unless($this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador), 403);
        $registro = $profissional->vinculosUnidades()->with('unidade.organizacao')->findOrFail($vinculo);
        $autorizador->exigir($request->user(), 'profissionais.administrar', $registro->unidade->organizacao_saude_id, $registro->unidade_saude_id);

        if (! $registro->unidade->ativo || ! $registro->unidade->organizacao?->ativo) {
            throw ValidationException::withMessages(['vinculo' => 'Não é possível reativar um vínculo com unidade ou organização inativa.']);
        }

        $registro->update(['ativo' => true]);

        $auditoria->registrar('profissional.vinculo_reativado', [
            'entidade_tipo' => $registro::class,
            'entidade_id' => $registro->id,
            'organizacao_saude_id' => $registro->unidade->organizacao_saude_id,
            'unidade_saude_id' => $registro->unidade_saude_id,
            'dados_anteriores' => ['ativo' => false],
            'dados_posteriores' => ['ativo' => true],
        ], $request);

        return back()->with('sucesso', 'Vínculo reativado com sucesso.');
    }

    private function formulario(Request $request, AutorizadorEscopado $autorizador, ?Profissional $profissional = null): Response
    {
        $unidadesPermitidas = $autorizador->unidadesPermitidas($request->user(), 'profissionais.administrar');
        $organizacoesPermitidas = $autorizador->organizacoesPermitidas($request->user(), 'usuarios.visualizar');
        $unidadesUsuarios = $autorizador->unidadesPermitidas($request->user(), 'usuarios.visualizar');
        $sistemaUsuarios = $autorizador->possuiEscopoSistema($request->user(), 'usuarios.visualizar');

        $usuarios = User::query()
            ->where('ativo', true)
            ->when(! $sistemaUsuarios, fn ($query) => $query->whereHas('atribuicoesPerfil', function ($query) use ($organizacoesPermitidas, $unidadesUsuarios): void {
                $query->where('ativo', true)->where(function ($query) use ($organizacoesPermitidas, $unidadesUsuarios): void {
                    $query->whereIn('organizacao_saude_id', $organizacoesPermitidas ?? [])
                        ->orWhereIn('unidade_saude_id', $unidadesUsuarios ?? []);
                });
            }))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Profissionais/Formulario', [
            'profissional' => $profissional,
            'usuarios' => $usuarios,
            'unidades' => UnidadeSaude::query()
                ->with('organizacao:id,nome,ativo')
                ->where('ativo', true)
                ->whereHas('organizacao', fn ($query) => $query->where('ativo', true))
                ->when($unidadesPermitidas !== null, fn ($query) => $query->whereKey($unidadesPermitidas))
                ->orderBy('nome')
                ->get(['id', 'organizacao_saude_id', 'nome', 'cnes']),
            'podeAdministrar' => $profissional
                ? $this->podeAcessar($request->user(), $profissional, 'profissionais.administrar', $autorizador)
                : $autorizador->possuiPermissao($request->user(), 'profissionais.administrar'),
            'exigeVinculoInicial' => ! $autorizador->possuiEscopoSistema($request->user(), 'profissionais.administrar'),
        ]);
    }

    private function dadosValidados(
        Request $request,
        ?Profissional $profissional = null,
        bool $unidadeInicialObrigatoria = false,
    ): array {
        $request->merge([
            'cpf' => ($cpf = preg_replace('/\D/', '', (string) $request->input('cpf'))) !== '' ? $cpf : null,
            'cns' => ($cns = preg_replace('/\D/', '', (string) $request->input('cns'))) !== '' ? $cns : null,
            'cbo' => ($cbo = preg_replace('/\D/', '', (string) $request->input('cbo'))) !== '' ? $cbo : null,
            'conselho_tipo' => ($conselho = trim((string) $request->input('conselho_tipo'))) !== '' ? mb_strtoupper($conselho) : null,
            'conselho_numero' => ($numero = trim((string) $request->input('conselho_numero'))) !== '' ? $numero : null,
            'conselho_uf' => ($uf = trim((string) $request->input('conselho_uf'))) !== '' ? mb_strtoupper($uf) : null,
        ]);

        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('profissionais', 'user_id')->ignore($profissional?->id)],
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'digits:11', Rule::unique('profissionais', 'cpf')->ignore($profissional?->id)],
            'cns' => ['nullable', 'digits:15', Rule::unique('profissionais', 'cns')->ignore($profissional?->id)],
            'categoria' => ['required', 'string', 'max:40'],
            'cbo' => ['nullable', 'digits_between:4,10'],
            'conselho_tipo' => ['nullable', 'string', 'max:20'],
            'conselho_numero' => ['nullable', 'string', 'max:30'],
            'conselho_uf' => ['nullable', 'string', 'size:2'],
            'ativo' => ['boolean'],
            'unidade_saude_id_inicial' => [$unidadeInicialObrigatoria ? 'required' : 'nullable', 'integer', 'exists:unidades_saude,id'],
        ]);
    }

    private function dadosVinculoValidados(Request $request): array
    {
        return $request->validate([
            'unidade_saude_id' => ['required', 'integer', 'exists:unidades_saude,id'],
            'vigente_de' => ['nullable', 'date'],
            'vigente_ate' => ['nullable', 'date', 'after_or_equal:vigente_de'],
        ]);
    }

    private function resolverUnidadeAtiva(?int $unidadeId): ?UnidadeSaude
    {
        if (! $unidadeId) {
            return null;
        }

        $unidade = UnidadeSaude::query()->with('organizacao')->findOrFail($unidadeId);

        if (! $unidade->ativo || ! $unidade->organizacao?->ativo) {
            throw ValidationException::withMessages(['unidade_saude_id' => 'Selecione uma unidade ativa de uma organização ativa.']);
        }

        return $unidade;
    }

    private function podeAcessar(User $usuario, Profissional $profissional, string $permissao, AutorizadorEscopado $autorizador): bool
    {
        if ($autorizador->possuiEscopoSistema($usuario, $permissao)) {
            return true;
        }

        $unidades = $autorizador->unidadesPermitidas($usuario, $permissao) ?? [];

        return $profissional->vinculosUnidades()->whereIn('unidade_saude_id', $unidades)->exists();
    }

    private function camposAuditaveis(): array
    {
        return [
            'user_id', 'nome', 'cpf', 'cns', 'categoria', 'cbo',
            'conselho_tipo', 'conselho_numero', 'conselho_uf', 'ativo',
        ];
    }

    private function dadosAuditaveisVinculo(VinculoProfissionalUnidade $vinculo): array
    {
        return [
            'profissional_id' => $vinculo->profissional_id,
            'unidade_saude_id' => $vinculo->unidade_saude_id,
            'vigente_de' => $vinculo->vigente_de?->toDateString(),
            'vigente_ate' => $vinculo->vigente_ate?->toDateString(),
            'ativo' => $vinculo->ativo,
        ];
    }

    private function auditarVinculo(
        Request $request,
        RegistradorAuditoria $auditoria,
        string $evento,
        Profissional $profissional,
        VinculoProfissionalUnidade $vinculo,
        UnidadeSaude $unidade,
    ): void {
        $auditoria->registrar($evento, [
            'entidade_tipo' => VinculoProfissionalUnidade::class,
            'entidade_id' => $vinculo->id,
            'organizacao_saude_id' => $unidade->organizacao_saude_id,
            'unidade_saude_id' => $unidade->id,
            'dados_posteriores' => $this->dadosAuditaveisVinculo($vinculo) + ['profissional_id' => $profissional->id],
        ], $request);
    }
}
