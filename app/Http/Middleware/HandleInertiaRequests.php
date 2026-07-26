<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => $this->dadosAutenticacao($request),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'sucesso' => fn () => $request->session()->get('sucesso'),
            ],
        ]);
    }

    private function dadosAutenticacao(Request $request): array
    {
        $usuario = $request->user();
        $chaves = [
            'organizacoes.visualizar', 'organizacoes.administrar',
            'unidades.visualizar', 'unidades.administrar',
            'usuarios.visualizar', 'usuarios.administrar',
            'perfis.visualizar', 'perfis.administrar',
            'profissionais.visualizar', 'profissionais.administrar',
            'auditoria.visualizar',
            'pacientes.visualizar', 'pacientes.administrar',
            'fila.visualizar', 'fila.administrar',
            'triagem.realizar', 'prontuario.visualizar', 'prontuario.registrar',
            'prescricao.emitir',
        ];

        $capacidades = array_fill_keys($chaves, false);

        if (! $usuario || ! $usuario->ativo) {
            return ['usuario' => null, 'capacidades' => $capacidades];
        }

        $concedidas = $usuario->atribuicoesPerfil()
            ->where('ativo', true)
            ->where(function ($query): void {
                $query->whereNull('vigente_de')->orWhere('vigente_de', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('vigente_ate')->orWhere('vigente_ate', '>=', now());
            })
            ->with(['perfil.permissoes'])
            ->get()
            ->filter(fn ($atribuicao) => $atribuicao->perfil?->ativo)
            ->flatMap(fn ($atribuicao) => $atribuicao->perfil->permissoes->pluck('chave'))
            ->unique();

        foreach ($concedidas as $chave) {
            $capacidades[$chave] = true;
        }

        return [
            'usuario' => $usuario->only(['id', 'name', 'email']),
            'capacidades' => $capacidades,
        ];
    }
}
