<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registros_auditoria', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('ator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 120);
            $table->string('entidade_tipo', 120)->nullable();
            $table->string('entidade_id', 80)->nullable();
            $table->string('nivel', 20)->default('informacao');
            $table->foreignId('organizacao_saude_id')->nullable()->constrained('organizacoes_saude')->nullOnDelete();
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude')->nullOnDelete();
            $table->jsonb('dados_anteriores')->nullable();
            $table->jsonb('dados_posteriores')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('ocorrido_em')->useCurrent();
            $table->index(['evento', 'ocorrido_em']);
            $table->index(['organizacao_saude_id', 'unidade_saude_id']);
        });

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION bloquear_mutacao_registro_auditoria()
RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'registros de auditoria são imutáveis';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER registros_auditoria_append_only
BEFORE UPDATE OR DELETE ON registros_auditoria
FOR EACH ROW EXECUTE FUNCTION bloquear_mutacao_registro_auditoria();
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS registros_auditoria_append_only ON registros_auditoria');
        DB::unprepared('DROP FUNCTION IF EXISTS bloquear_mutacao_registro_auditoria');
        Schema::dropIfExists('registros_auditoria');
    }
};
