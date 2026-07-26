<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    unidades: { data: Array<any>; links: Array<any>; total: number };
    organizacoes: Array<any>;
    filtros: { busca?: string; situacao?: string; organizacao_id?: number | null };
    podeAdministrar: boolean;
}>();
const filtros = reactive({ busca: props.filtros.busca ?? '', situacao: props.filtros.situacao ?? '', organizacao_id: props.filtros.organizacao_id ?? '' });
const pesquisar = () => router.get('/unidades', filtros, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Unidades de saúde" />
    <AppShell titulo="Unidades de saúde">
        <section class="pagina-cabecalho">
            <div><p class="eyebrow">Estrutura operacional</p><h2>Unidades de atendimento</h2><p>Cadastre e acompanhe as unidades vinculadas a cada organização.</p></div>
            <Link v-if="podeAdministrar" class="botao botao--primario" href="/unidades/nova">Nova unidade</Link>
        </section>
        <form class="filtros-card filtros-card--quatro" @submit.prevent="pesquisar">
            <input v-model="filtros.busca" class="campo-formulario__controle" placeholder="Nome, CNES ou tipo" />
            <select v-model="filtros.organizacao_id" class="campo-formulario__controle"><option value="">Todas as organizações</option><option v-for="o in organizacoes" :key="o.id" :value="o.id">{{ o.nome }}</option></select>
            <select v-model="filtros.situacao" class="campo-formulario__controle"><option value="">Todas as situações</option><option value="ativos">Ativas</option><option value="inativos">Inativas</option></select>
            <button class="botao botao--secundario">Pesquisar</button>
        </form>
        <section class="tabela-card">
            <table class="tabela-dados">
                <thead><tr><th>Unidade</th><th>Organização</th><th>CNES</th><th>Tipo</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in unidades.data" :key="item.id">
                        <td><strong>{{ item.nome }}</strong></td><td>{{ item.organizacao?.nome }}</td><td>{{ item.cnes || '—' }}</td><td>{{ item.tipo }}</td>
                        <td><span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativa' : 'Inativa' }}</span></td>
                        <td><Link :href="`/unidades/${item.id}/editar`">{{ podeAdministrar ? 'Editar' : 'Visualizar' }}</Link></td>
                    </tr>
                    <tr v-if="unidades.data.length === 0"><td colspan="6" class="estado-vazio">Nenhuma unidade encontrada.</td></tr>
                </tbody>
            </table>
            <nav v-if="unidades.links.length > 3" class="paginacao"><Link v-for="link in unidades.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="{ ativo: link.active, desabilitado: !link.url }" /></nav>
        </section>
    </AppShell>
</template>
