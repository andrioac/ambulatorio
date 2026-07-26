<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppBrand from '../components/AppBrand.vue';

const props = defineProps<{ status: number }>();

const mensagens: Record<number, { titulo: string; descricao: string }> = {
    403: { titulo: 'Acesso não autorizado', descricao: 'Seu usuário não possui permissão para acessar este recurso.' },
    404: { titulo: 'Página não encontrada', descricao: 'O endereço informado não existe ou foi removido.' },
    419: { titulo: 'Sessão expirada', descricao: 'Sua sessão expirou. Entre novamente para continuar com segurança.' },
    422: { titulo: 'Não foi possível processar', descricao: 'Revise os dados informados e tente novamente.' },
};

const mensagem = mensagens[props.status] ?? {
    titulo: 'Ocorreu um erro',
    descricao: 'Não foi possível concluir a solicitação.',
};
</script>

<template>
    <Head :title="mensagem.titulo" />
    <div class="auth-layout">
        <header class="auth-header"><AppBrand /><span class="auth-header__seguranca">Ambiente seguro</span></header>
        <main class="auth-page auth-page--centralizada">
            <section class="auth-card auth-card--compacta erro-page">
                <div class="erro-page__codigo">{{ status }}</div>
                <h2>{{ mensagem.titulo }}</h2>
                <p>{{ mensagem.descricao }}</p>
                <div class="erro-page__acoes">
                    <Link class="botao botao--primario" href="/">Voltar ao início</Link>
                    <Link v-if="status === 419" class="botao botao--secundario" href="/entrar">Entrar novamente</Link>
                </div>
            </section>
        </main>
    </div>
</template>
