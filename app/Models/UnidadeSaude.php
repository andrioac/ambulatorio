<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadeSaude extends Model
{
    protected $table = 'unidades_saude';
    protected $fillable = ['organizacao_saude_id', 'nome', 'cnes', 'tipo', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
    public function organizacao(): BelongsTo { return $this->belongsTo(OrganizacaoSaude::class, 'organizacao_saude_id'); }
}
