<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AtribuicaoPerfil extends Model
{
    use SoftDeletes;

    protected $table = 'atribuicoes_perfil';

    protected $fillable = ['user_id', 'perfil_id', 'tipo_escopo', 'organizacao_saude_id', 'unidade_saude_id', 'vigente_de', 'vigente_ate', 'ativo'];

    protected function casts(): array
    {
        return ['vigente_de' => 'datetime', 'vigente_ate' => 'datetime', 'ativo' => 'boolean'];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estaVigente(): bool
    {
        return $this->ativo && (! $this->vigente_de || $this->vigente_de->isPast()) && (! $this->vigente_ate || $this->vigente_ate->isFuture());
    }
}
