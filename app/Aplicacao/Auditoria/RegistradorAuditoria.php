<?php

namespace App\Aplicacao\Auditoria;

use App\Models\RegistroAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RegistradorAuditoria
{
    private const SENSIVEIS = ['password', 'senha', 'token', 'authorization', 'cookie', 'secret'];

    public function registrar(string $evento, array $contexto = [], ?Request $request = null): RegistroAuditoria
    {
        $request ??= request();

        return RegistroAuditoria::query()->create([
            'ator_user_id' => auth()->id(),
            'evento' => $evento,
            'entidade_tipo' => Arr::pull($contexto, 'entidade_tipo'),
            'entidade_id' => Arr::pull($contexto, 'entidade_id'),
            'nivel' => Arr::pull($contexto, 'nivel', 'informacao'),
            'organizacao_saude_id' => Arr::pull($contexto, 'organizacao_saude_id'),
            'unidade_saude_id' => Arr::pull($contexto, 'unidade_saude_id'),
            'dados_anteriores' => $this->sanitizar(Arr::pull($contexto, 'dados_anteriores')),
            'dados_posteriores' => $this->sanitizar(Arr::pull($contexto, 'dados_posteriores', $contexto)),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'ocorrido_em' => now(),
        ]);
    }

    private function sanitizar(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        $resultado = [];

        foreach ($valor as $chave => $item) {
            $resultado[$chave] = in_array(strtolower((string) $chave), self::SENSIVEIS, true)
                ? '[REMOVIDO]'
                : $this->sanitizar($item);
        }

        return $resultado;
    }
}
