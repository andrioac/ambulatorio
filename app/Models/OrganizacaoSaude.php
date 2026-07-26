<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizacaoSaude extends Model
{
    protected $table = 'organizacoes_saude';

    protected $fillable = ['nome', 'sigla', 'cnpj', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(UnidadeSaude::class, 'organizacao_saude_id');
    }
}
