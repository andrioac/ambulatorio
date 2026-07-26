<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profissionais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nome');
            $table->string('cpf', 11)->nullable()->unique();
            $table->string('cns', 15)->nullable()->unique();
            $table->string('categoria', 40);
            $table->string('cbo', 10)->nullable();
            $table->string('conselho_tipo', 20)->nullable();
            $table->string('conselho_numero', 30)->nullable();
            $table->char('conselho_uf', 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['categoria', 'ativo']);
        });

        Schema::create('vinculos_profissionais_unidades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignId('unidade_saude_id')->constrained('unidades_saude')->restrictOnDelete();
            $table->date('vigente_de')->nullable();
            $table->date('vigente_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['profissional_id', 'unidade_saude_id', 'vigente_de'], 'vinculo_profissional_unidade_inicio_unico');
            $table->index(['unidade_saude_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculos_profissionais_unidades');
        Schema::dropIfExists('profissionais');
    }
};
