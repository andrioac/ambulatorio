<?php

namespace App\Aplicacao\Profissionais;

use App\Models\Profissional;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SalvarProfissional
{
    public function executar(array $dados, ?Profissional $profissional = null): Profissional
    {
        return DB::transaction(function () use ($dados, $profissional): Profissional {
            $profissional ??= new Profissional;

            $profissional->fill([
                'user_id' => Arr::get($dados, 'user_id'),
                'nome' => trim((string) Arr::get($dados, 'nome')),
                'cpf' => $this->somenteDigitosOuNulo(Arr::get($dados, 'cpf')),
                'cns' => $this->somenteDigitosOuNulo(Arr::get($dados, 'cns')),
                'categoria' => Str::lower(trim((string) Arr::get($dados, 'categoria'))),
                'cbo' => $this->somenteDigitosOuNulo(Arr::get($dados, 'cbo')),
                'conselho_tipo' => $this->textoMaiusculoOuNulo(Arr::get($dados, 'conselho_tipo')),
                'conselho_numero' => $this->textoOuNulo(Arr::get($dados, 'conselho_numero')),
                'conselho_uf' => $this->textoMaiusculoOuNulo(Arr::get($dados, 'conselho_uf')),
                'ativo' => (bool) Arr::get($dados, 'ativo', true),
            ]);

            $profissional->save();

            return $profissional->refresh();
        });
    }

    private function somenteDigitosOuNulo(mixed $valor): ?string
    {
        $normalizado = preg_replace('/\D+/', '', (string) $valor);

        return $normalizado === '' ? null : $normalizado;
    }

    private function textoOuNulo(mixed $valor): ?string
    {
        $normalizado = trim((string) $valor);

        return $normalizado === '' ? null : $normalizado;
    }

    private function textoMaiusculoOuNulo(mixed $valor): ?string
    {
        $normalizado = $this->textoOuNulo($valor);

        return $normalizado === null ? null : Str::upper($normalizado);
    }
}
