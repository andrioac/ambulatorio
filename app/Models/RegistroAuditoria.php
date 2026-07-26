<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class RegistroAuditoria extends Model
{
    public $timestamps = false;
    protected $table = 'registros_auditoria';
    protected $guarded = [];
    protected function casts(): array { return ['dados_anteriores' => 'array', 'dados_posteriores' => 'array', 'ocorrido_em' => 'immutable_datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Registro de auditoria é imutável.'));
        static::deleting(fn () => throw new LogicException('Registro de auditoria é imutável.'));
    }
}
