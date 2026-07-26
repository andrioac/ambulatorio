<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Perfil extends Model
{
    protected $table = 'perfis';
    protected $fillable = ['nome', 'chave', 'protegido', 'ativo'];
    protected function casts(): array { return ['protegido' => 'boolean', 'ativo' => 'boolean']; }
    public function permissoes(): BelongsToMany { return $this->belongsToMany(Permissao::class, 'perfil_permissao'); }
}
