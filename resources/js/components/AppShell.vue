<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppBrand from './AppBrand.vue';

defineProps<{ titulo?: string }>();
const sair = () => router.post('/sair');
</script>

<template>
    <div class="app-shell">
        <aside class="app-sidebar">
            <AppBrand clara />
            <nav class="app-nav" aria-label="Navegação principal">
                <Link href="/" class="app-nav__item app-nav__item--ativo">Visão geral</Link>
                <span class="app-nav__grupo">Atendimento</span>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Pacientes</a>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Fila de atendimento</a>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Triagem</a>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Prontuário</a>
                <span class="app-nav__grupo">Administração</span>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Unidades de saúde</a>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Usuários e perfis</a>
                <a class="app-nav__item app-nav__item--indisponivel" href="#">Auditoria</a>
            </nav>
            <div class="app-sidebar__rodape">
                <span>Ambiente protegido</span>
                <small>Dados sensíveis com acesso controlado</small>
            </div>
        </aside>

        <div class="app-shell__corpo">
            <header class="app-header">
                <div>
                    <p class="app-header__contexto">Ambulatório Inteligente</p>
                    <h1>{{ titulo ?? 'Visão geral' }}</h1>
                </div>
                <div class="app-header__acoes">
                    <span class="app-header__seguranca">Ambiente seguro</span>
                    <button class="botao botao--secundario" type="button" @click="sair">Sair</button>
                </div>
            </header>
            <main class="app-content"><slot /></main>
        </div>
    </div>
</template>
