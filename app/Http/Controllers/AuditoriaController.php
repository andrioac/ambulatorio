<?php

namespace App\Http\Controllers;

use App\Aplicacao\Autorizacao\AutorizadorEscopado;
use App\Models\OrganizacaoSaude;
use App\Models\RegistroAuditoria;
use App\Models\UnidadeSaude;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AuditoriaController extends Controller
{
    public function index(Request $request, AutorizadorEscopado $autorizador): Response
    {
        $filtros = [
            'evento' => trim((string) $request->query('evento')),
            'ator' => trim((string) $request->query('ator')),
            'entidade' => trim((string) $request->query('entidade')),
            'organizacao_id' => $request->integer('organizacao_id') ?: null,
            'unidade_id' => $request->integer('unidade_id') ?: null,
            'de' => $request->query('de'),
            'ate' => $request->query('ate'),
        ];

        $sistema = $autorizador->possuiEscopoSistema($request->user(), 'auditoria.visualizar');
        $organizacoes = $autorizador->organizacoesPermitidas($request->user(), 'auditoria.visualizar') ?? [];
        $unidades = $autorizador->unidadesPermitidas($request->user(), 'auditoria.visualizar') ?? [];

        $registros = RegistroAuditoria::query()
            ->with(['ator:id,name,email', 'organizacao:id,nome', 'unidade:id,nome'])
            ->when(! $sistema, fn ($query) => $query->where(function ($query) use ($request, $organizacoes, $unidades): void {
                $query->where('ator_user_id', $request->user()->id)
                    ->orWhereIn('organizacao_saude_id', $organizacoes)
                    ->orWhereIn('unidade_saude_id', $unidades);
            }))
            ->when($filtros['evento'] !== '', fn ($query) => $query->where('evento', 'like', "%{$filtros['evento']}%"))
            ->when($filtros['ator'] !== '', fn ($query) => $query->whereHas('ator', function ($query) use ($filtros): void {
                $query->where('name', 'like', "%{$filtros['ator']}%")
                    ->orWhere('email', 'like', "%{$filtros['ator']}%");
            }))
            ->when($filtros['entidade'] !== '', fn ($query) => $query->where(function ($query) use ($filtros): void {
                $query->where('entidade_tipo', 'like', "%{$filtros['entidade']}%")
                    ->orWhere('entidade_id', (string) $filtros['entidade']);
            }))
            ->when($filtros['organizacao_id'], fn ($query) => $query->where('organizacao_saude_id', $filtros['organizacao_id']))
            ->when($filtros['unidade_id'], fn ($query) => $query->where('unidade_saude_id', $filtros['unidade_id']))
            ->when($filtros['de'], fn ($query) => $query->where('ocorrido_em', '>=', $filtros['de'].' 00:00:00'))
            ->when($filtros['ate'], fn ($query) => $query->where('ocorrido_em', '<=', $filtros['ate'].' 23:59:59'))
            ->latest('ocorrido_em')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Auditoria/Index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'organizacoes' => OrganizacaoSaude::query()
                ->when(! $sistema, fn ($query) => $query->whereKey($organizacoes))
                ->orderBy('nome')->get(['id', 'nome']),
            'unidades' => UnidadeSaude::query()
                ->when(! $sistema, fn ($query) => $query->whereKey($unidades))
                ->orderBy('nome')->get(['id', 'organizacao_saude_id', 'nome']),
        ]);
    }
}
