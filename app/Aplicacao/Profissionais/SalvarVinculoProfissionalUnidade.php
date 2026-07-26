<?php

namespace App\Aplicacao\Profissionais;

use App\Models\Profissional;
use App\Models\UnidadeSaude;
use App\Models\VinculoProfissionalUnidade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SalvarVinculoProfissionalUnidade
{
    public function executar(
        Profissional $profissional,
        UnidadeSaude $unidade,
        ?string $vigenteDe = null,
        ?string $vigenteAte = null,
        bool $ativo = true,
        ?VinculoProfissionalUnidade $vinculo = null,
    ): VinculoProfissionalUnidade {
        $inicio = $vigenteDe === null ? null : CarbonImmutable::parse($vigenteDe)->startOfDay();
        $fim = $vigenteAte === null ? null : CarbonImmutable::parse($vigenteAte)->startOfDay();

        if ($inicio !== null && $fim !== null && $fim->lt($inicio)) {
            throw new \DomainException('A data final do vínculo não pode ser anterior à data inicial.');
        }

        return DB::transaction(function () use ($profissional, $unidade, $inicio, $fim, $ativo, $vinculo): VinculoProfissionalUnidade {
            $vinculo ??= new VinculoProfissionalUnidade;

            $vinculo->fill([
                'profissional_id' => $profissional->id,
                'unidade_saude_id' => $unidade->id,
                'vigente_de' => $inicio?->toDateString(),
                'vigente_ate' => $fim?->toDateString(),
                'ativo' => $ativo,
            ]);

            $vinculo->save();

            return $vinculo->refresh();
        });
    }
}
