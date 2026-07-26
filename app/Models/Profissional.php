<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profissional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'profissionais';

    protected $fillable = [
        'user_id',
        'nome',
        'cpf',
        'cns',
        'categoria',
        'cbo',
        'conselho_tipo',
        'conselho_numero',
        'conselho_uf',
        'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vinculosUnidades(): HasMany
    {
        return $this->hasMany(VinculoProfissionalUnidade::class);
    }
}
