<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('ativo')->default(true)->after('password');
        });

        Schema::create('permissoes', function (Blueprint $table): void {
            $table->id();
            $table->string('chave')->unique();
            $table->string('descricao');
            $table->timestamps();
        });

        Schema::create('perfis', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('chave')->unique();
            $table->boolean('protegido')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('perfil_permissao', function (Blueprint $table): void {
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $table->primary(['perfil_id', 'permissao_id']);
        });

        Schema::create('atribuicoes_perfil', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('perfil_id')->constrained('perfis')->restrictOnDelete();
            $table->string('tipo_escopo', 20);
            $table->foreignId('organizacao_saude_id')->nullable()->constrained('organizacoes_saude')->restrictOnDelete();
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude')->restrictOnDelete();
            $table->timestamp('vigente_de')->nullable();
            $table->timestamp('vigente_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'ativo']);
        });

        DB::statement("ALTER TABLE atribuicoes_perfil ADD CONSTRAINT atribuicoes_escopo_valido CHECK ((tipo_escopo = 'sistema' AND organizacao_saude_id IS NULL AND unidade_saude_id IS NULL) OR (tipo_escopo = 'organizacao' AND organizacao_saude_id IS NOT NULL AND unidade_saude_id IS NULL) OR (tipo_escopo = 'unidade' AND organizacao_saude_id IS NULL AND unidade_saude_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('atribuicoes_perfil');
        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('perfis');
        Schema::dropIfExists('permissoes');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('ativo'));
    }
};
