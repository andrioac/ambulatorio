<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizacoes_saude', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('sigla', 20)->nullable();
            $table->string('cnpj', 14)->nullable()->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('unidades_saude', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organizacao_saude_id')->constrained('organizacoes_saude')->restrictOnDelete();
            $table->string('nome');
            $table->string('cnes', 7)->nullable()->unique();
            $table->string('tipo', 50)->default('ubs');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index(['organizacao_saude_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_saude');
        Schema::dropIfExists('organizacoes_saude');
    }
};
