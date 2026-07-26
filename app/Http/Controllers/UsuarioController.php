<?php

namespace App\Http\Controllers;

use App\Aplicacao\Auditoria\RegistradorAuditoria;
use App\Aplicacao\Autorizacao\AtribuirPerfilUsuario;
use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\AtribuicaoPerfil;
use App\Models\OrganizacaoSaude;
use App\Models\Perfil;
use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class UsuarioController extends Controller
{
    public function index(Request $request, AutorizadorEscopado $autorizador): Response
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = (string) $request->query('situacao');
        $escopoSistema = $autorizador->possuiEscopoSistema($request->user(), 'usuarios.visualizar');
        $organizacoes = $autorizador->organizacoesPermitidas($request->user(), 'usuarios.visualizar') ?? [];
        $unidades = $autorizador->unidadesPermitidas($request->user(), 'usuarios.visualizar') ?? [];

        $usuarios = User::query()
            ->with(['profissional:id,user_id,nome,categoria'])
            ->withCount(['atribuicoesPerfil as atribuicoes_ativas_count' => fn ($query) => $query->where('ativo', true)])
            ->when(! $escopoSistema, fn ($query) => $query->whereHas('atribuicoesPerfil', function ($query) use ($organizacoes, $unidades): void {
                $query->where('ativo', true)->where(function ($query) use ($organizacoes, $unidades): void {
                    $query->where(function ($query) use ($organizacoes): void {
                        $query->where('tipo_escopo', 'organizacao')->whereIn('organizacao_saude_id', $organizacoes);
                    })->orWhere(function ($query) use ($unidades): void {
                        $query->where('tipo_escopo', 'unidade')->whereIn('unidade_saude_id', $unidades);
                    });
                });
            }))
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $query->where('name', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%");
            }))
            ->when(in_array($situacao, ['ativos', 'inativos'], true), fn ($query) => $query->where('ativo', $situacao === 'ativos'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => ['busca' => $busca, 'situacao' => $situacao],
            'podeAdministrar' => $autorizador->possuiPermissao($request->user(), 'usuarios.administrar'),
        ]);
    }

    public function create(Request $request, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($autorizador->possuiPermissao($request->user(), 'usuarios.administrar'), 403);

        return Inertia::render('Usuarios/Formulario', array_merge(
            ['usuario' => null, 'podeAdministrar' => true],
            $this->catalogos($request, $autorizador),
        ));
    }

    public function store(
        Request $request,
        AutorizadorEscopado $autorizador,
        AtribuirPerfilUsuario $atribuir,
        RegistradorAuditoria $auditoria,
    ): RedirectResponse {
        abort_unless($autorizador->possuiPermissao($request->user(), 'usuarios.administrar'), 403);
        $sistema = $autorizador->possuiEscopoSistema($request->user(), 'usuarios.administrar');
        $dados = $this->dadosValidados($request, senhaObrigatoria: true, atribuicaoObrigatoria: ! $sistema);

        [$usuario, $atribuicao] = DB::transaction(function () use ($request, $dados, $atribuir): array {
            $usuario = User::query()->create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'password' => $dados['password'],
                'ativo' => $dados['ativo'],
            ]);

            $atribuicao = null;

            if (! empty($dados['perfil_id'])) {
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
            }

            return [$usuario, $atribuicao];
        });

        $auditoria->registrar('usuario.criado', [
            'entidade_tipo' => User::class,
            'entidade_id' => $usuario->id,
            'dados_posteriores' => $usuario->only(['name', 'email', 'ativo']),
        ], $request);

        if ($atribuicao) {
            $this->auditarAtribuicao($request, $auditoria, $usuario, $atribuicao);
        }

        return redirect()->route('usuarios.edit', $usuario)->with('sucesso', 'Usuário cadastrado com sucesso.');
    }

    public function edit(Request $request, User $usuario, AutorizadorEscopado $autorizador): Response
    {
        abort_unless($this->podeVisualizar($request->user(), $usuario, $autorizador), 403);
        $usuario->load(['profissional:id,user_id,nome,categoria', 'atribuicoesPerfil.perfil', 'atribuicoesPerfil.organizacao', 'atribuicoesPerfil.unidade.organizacao']);

        return Inertia::render('Usuarios/Formulario', array_merge([
            'usuario' => $usuario,
            'podeAdministrar' => $this->podeAdministrar($request->user(), $usuario, $autorizador),
        ], $this->catalogos($request, $autorizador)));
    }

    public function update(
        Request $request,
        User $usuario,
        AutorizadorEscopado $autorizador,
        RegistradorAuditoria $auditoria,
    ): RedirectResponse {
        abort_unless($this->podeAdministrar($request->user(), $usuario, $autorizador), 403);
        $dados = $this->dadosValidados($request, $usuario);

        if ($usuario->is($request->user()) && ! $dados['ativo']) {
            throw ValidationException::withMessages(['ativo' => 'Você não pode inativar o próprio usuário.']);
        }

        if ($usuario->ativo && ! $dados['ativo']) {
            $this->protegerUltimoSuperadministrador($usuario);
        }

        $anteriores = $usuario->only(['name', 'email', 'ativo']);
        $atualizacao = [
            'name' => $dados['name'],
            'email' => $dados['email'],
            'ativo' => $dados['ativo'],
        ];

        if (! empty($dados['password'])) {
            $atualizacao['password'] = $dados['password'];
        }

        $usuario->update($atualizacao);

        $auditoria->registrar('usuario.atualizado', [
            'entidade_tipo' => User::class,
            'entidade_id' => $usuario->id,
            'dados_anteriores' => $anteriores,
            'dados_posteriores' => $usuario->fresh()->only(['name', 'email', 'ativo']),
        ], $request);

        return back()->with('sucesso', 'Usuário atualizado com sucesso.');
    }

    private function dadosValidados(
        Request $request,
        ?User $usuario = null,
        bool $senhaObrigatoria = false,
        bool $atribuicaoObrigatoria = false,
    ): array {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$senhaObrigatoria ? 'required' : 'nullable', 'confirmed', RegraSenha::defaults()],
            'ativo' => ['boolean'],
            'perfil_id' => [$atribuicaoObrigatoria ? 'required' : 'nullable', 'integer', 'exists:perfis,id'],
            'tipo_escopo' => [$atribuicaoObrigatoria ? 'required' : 'nullable', Rule::in(['sistema', 'organizacao', 'unidade'])],
            'organizacao_saude_id' => ['nullable', 'integer', 'exists:organizacoes_saude,id'],
            'unidade_saude_id' => ['nullable', 'integer', 'exists:unidades_saude,id'],
            'vigente_de' => ['nullable', 'date'],
            'vigente_ate' => ['nullable', 'date', 'after_or_equal:vigente_de'],
        ]);
    }

    private function catalogos(Request $request, AutorizadorEscopado $autorizador): array
    {
        $organizacoes = $autorizador->organizacoesPermitidas($request->user(), 'usuarios.administrar');
        $unidades = $autorizador->unidadesPermitidas($request->user(), 'usuarios.administrar');
        $podeEscopoSistema = $autorizador->possuiEscopoSistema($request->user(), 'usuarios.administrar');

        return [
            'perfis' => Perfil::query()
                ->with('permissoes:id,chave')
                ->where('ativo', true)
                ->when(! $podeEscopoSistema, fn ($query) => $query->where('chave', '!=', 'superadministrador_sistema'))
                ->orderBy('nome')
                ->get(),
            'organizacoes' => OrganizacaoSaude::query()
                ->when($organizacoes !== null, fn ($query) => $query->whereKey($organizacoes))
                ->where('ativo', true)
                ->orderBy('nome')
                ->get(['id', 'nome']),
            'unidades' => UnidadeSaude::query()
                ->with('organizacao:id,nome')
                ->when($unidades !== null, fn ($query) => $query->whereKey($unidades))
                ->where('ativo', true)
                ->whereHas('organizacao', fn ($query) => $query->where('ativo', true))
                ->orderBy('nome')
                ->get(['id', 'organizacao_saude_id', 'nome']),
            'podeEscopoSistema' => $podeEscopoSistema,
        ];
    }

    private function podeVisualizar(User $ator, User $usuario, AutorizadorEscopado $autorizador): bool
    {
        if ($autorizador->possuiEscopoSistema($ator, 'usuarios.visualizar')) {
            return true;
        }

        $organizacoes = $autorizador->organizacoesPermitidas($ator, 'usuarios.visualizar') ?? [];
        $unidades = $autorizador->unidadesPermitidas($ator, 'usuarios.visualizar') ?? [];

        return $usuario->atribuicoesPerfil()->where('ativo', true)
            ->where(function ($query) use ($organizacoes, $unidades): void {
                $query->where(function ($query) use ($organizacoes): void {
                    $query->where('tipo_escopo', 'organizacao')->whereIn('organizacao_saude_id', $organizacoes);
                })->orWhere(function ($query) use ($unidades): void {
                    $query->where('tipo_escopo', 'unidade')->whereIn('unidade_saude_id', $unidades);
                });
            })->exists();
    }

    private function podeAdministrar(User $ator, User $usuario, AutorizadorEscopado $autorizador): bool
    {
        if ($autorizador->possuiEscopoSistema($ator, 'usuarios.administrar')) {
            return true;
        }

        $organizacoes = $autorizador->organizacoesPermitidas($ator, 'usuarios.administrar') ?? [];
        $unidades = $autorizador->unidadesPermitidas($ator, 'usuarios.administrar') ?? [];
        $atribuicoes = $usuario->atribuicoesPerfil()->where('ativo', true)->get();

        if ($atribuicoes->isEmpty() || $atribuicoes->contains('tipo_escopo', 'sistema')) {
            return false;
        }

        return $atribuicoes->every(fn ($atribuicao) => match ($atribuicao->tipo_escopo) {
            'organizacao' => in_array($atribuicao->organizacao_saude_id, $organizacoes, true),
            'unidade' => in_array($atribuicao->unidade_saude_id, $unidades, true),
            default => false,
        });
    }

    private function protegerUltimoSuperadministrador(User $usuario): void
    {
        $possuiSuperadministrador = $usuario->atribuicoesPerfil()
            ->where('ativo', true)
            ->whereHas('perfil', fn ($query) => $query
                ->where('chave', 'superadministrador_sistema')
                ->where('ativo', true))
            ->exists();

        if (! $possuiSuperadministrador) {
            return;
        }

        $outrosAtivos = AtribuicaoPerfil::query()
            ->where('ativo', true)
            ->where('user_id', '!=', $usuario->id)
            ->whereHas('usuario', fn ($query) => $query->where('ativo', true))
            ->whereHas('perfil', fn ($query) => $query
                ->where('chave', 'superadministrador_sistema')
                ->where('ativo', true))
            ->exists();

        if (! $outrosAtivos) {
            throw ValidationException::withMessages([
                'ativo' => 'O sistema deve manter ao menos um superadministrador ativo.',
            ]);
        }
    }

    private function auditarAtribuicao(
        Request $request,
        RegistradorAuditoria $auditoria,
        User $usuario,
        AtribuicaoPerfil $atribuicao,
    ): void {
        $atribuicao->loadMissing('unidade');
        $organizacaoId = $atribuicao->organizacao_saude_id
            ?? $atribuicao->unidade?->organizacao_saude_id;

        $auditoria->registrar('usuario.perfil_atribuido', [
            'entidade_tipo' => AtribuicaoPerfil::class,
            'entidade_id' => $atribuicao->id,
            'organizacao_saude_id' => $organizacaoId,
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
    }
}
