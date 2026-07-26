<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PerfilController extends Controller
{
    public function index(Request $request): Response
    {
        $busca = trim((string) $request->query('busca'));

        $perfis = Perfil::query()
            ->with(['permissoes:id,chave,descricao'])
            ->withCount(['atribuicoes as atribuicoes_ativas_count' => fn ($query) => $query->where('ativo', true)])
            ->when($busca !== '', fn ($query) => $query->where(function ($query) use ($busca): void {
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('chave', 'like', "%{$busca}%")
                    ->orWhereHas('permissoes', fn ($query) => $query->where('chave', 'like', "%{$busca}%"));
            }))
            ->orderByDesc('protegido')
            ->orderBy('nome')
            ->get();

        return Inertia::render('Perfis/Index', [
            'perfis' => $perfis,
            'permissoes' => Permissao::query()->orderBy('chave')->get(['id', 'chave', 'descricao']),
            'filtros' => ['busca' => $busca],
        ]);
    }
}
