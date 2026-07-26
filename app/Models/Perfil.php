<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    protected $table = 'perfis';

    protected $fillable = ['nome', 'chave', 'protegido', 'ativo'];

    protected function casts(): array
    {
        return ['protegido' => 'boolean', 'ativo' => 'boolean'];
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao');
    }

    public function atribuicoes(): HasMany
    {
        return $this->hasMany(AtribuicaoPerfil::class);
    }
}
