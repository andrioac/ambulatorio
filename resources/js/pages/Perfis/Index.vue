<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{ perfis: Array<any>; permissoes: Array<any>; filtros: { busca?: string } }>();
const filtros = reactive({ busca: props.filtros.busca ?? '' });
const pesquisar = () => router.get('/perfis', filtros, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Perfis e permissões" />
    <AppShell titulo="Perfis e permissões">
        <section class="pagina-cabecalho"><div><p class="eyebrow">Catálogo de autorização</p><h2>Perfis protegidos</h2><p>Consulte as permissões concedidas por cada perfil estrutural do sistema.</p></div></section>
        <div class="mensagem mensagem--informacao">Os perfis estruturais são mantidos pelo catálogo do sistema. A criação de perfis personalizados depende da definição futura sobre alcance global ou por organização.</div>
        <form class="filtros-card filtros-card--simples" @submit.prevent="pesquisar"><input v-model="filtros.busca" class="campo-formulario__controle" placeholder="Perfil ou permissão" /><button class="botao botao--secundario">Pesquisar</button></form>
        <section class="cards-lista">
            <article v-for="perfil in perfis" :key="perfil.id" class="form-card perfil-card">
                <div class="form-card__cabecalho"><div><p class="eyebrow">{{ perfil.chave }}</p><h2>{{ perfil.nome }}</h2></div><div class="acoes-inline"><span v-if="perfil.protegido" class="status status--protegido">Protegido</span><span class="status" :class="perfil.ativo ? 'status--ativo' : 'status--inativo'">{{ perfil.ativo ? 'Ativo' : 'Inativo' }}</span></div></div>
                <p>{{ perfil.atribuicoes_ativas_count }} atribuição(ões) ativa(s)</p>
                <div class="chips"><span v-for="permissao in perfil.permissoes" :key="permissao.id" class="chip">{{ permissao.chave }}</span></div>
            </article>
            <p v-if="perfis.length === 0" class="estado-vazio form-card">Nenhum perfil encontrado.</p>
        </section>
    </AppShell>
</template>
