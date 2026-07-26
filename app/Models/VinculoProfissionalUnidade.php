<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VinculoProfissionalUnidade extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vinculos_profissionais_unidades';

    protected $fillable = [
        'profissional_id',
        'unidade_saude_id',
        'vigente_de',
        'vigente_ate',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'vigente_de' => 'date',
            'vigente_ate' => 'date',
            'ativo' => 'boolean',
        ];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class, 'unidade_saude_id');
    }

    public function estaVigente(): bool
    {
        $hoje = now()->startOfDay();

        return $this->ativo
            && ($this->vigente_de === null || $this->vigente_de->lte($hoje))
            && ($this->vigente_ate === null || $this->vigente_ate->gte($hoje));
    }
}
