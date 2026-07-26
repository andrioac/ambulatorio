<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RegistroAuditoria extends Model
{
    public $timestamps = false;

    protected $table = 'registros_auditoria';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['dados_anteriores' => 'array', 'dados_posteriores' => 'array', 'ocorrido_em' => 'immutable_datetime'];
    }

    public function ator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ator_user_id');
    }

    public function organizacao(): BelongsTo
    {
        return $this->belongsTo(OrganizacaoSaude::class, 'organizacao_saude_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class, 'unidade_saude_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Registro de auditoria é imutável.'));
        static::deleting(fn () => throw new LogicException('Registro de auditoria é imutável.'));
    }
}
