<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    organizacoes: { data: Array<any>; links: Array<any>; total: number };
    filtros: { busca?: string; situacao?: string };
    podeAdministrar: boolean;
}>();

const filtros = reactive({ busca: props.filtros.busca ?? '', situacao: props.filtros.situacao ?? '' });
const pesquisar = () => router.get('/organizacoes', filtros, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Organizações" />
    <AppShell titulo="Organizações">
        <section class="pagina-cabecalho">
            <div><p class="eyebrow">Estrutura institucional</p><h2>Organizações de saúde</h2><p>Gerencie as instituições responsáveis pelas unidades de atendimento.</p></div>
            <Link v-if="podeAdministrar" class="botao botao--primario" href="/organizacoes/nova">Nova organização</Link>
        </section>

        <form class="filtros-card" @submit.prevent="pesquisar">
            <input v-model="filtros.busca" class="campo-formulario__controle" placeholder="Nome, sigla ou CNPJ" />
            <select v-model="filtros.situacao" class="campo-formulario__controle">
                <option value="">Todas as situações</option><option value="ativos">Ativas</option><option value="inativos">Inativas</option>
            </select>
            <button class="botao botao--secundario">Pesquisar</button>
        </form>

        <section class="tabela-card">
            <table class="tabela-dados">
                <thead><tr><th>Organização</th><th>CNPJ</th><th>Unidades</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in organizacoes.data" :key="item.id">
                        <td><strong>{{ item.nome }}</strong><small>{{ item.sigla || 'Sem sigla' }}</small></td>
                        <td>{{ item.cnpj || '—' }}</td>
                        <td>{{ item.unidades_count }}</td>
                        <td><span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativa' : 'Inativa' }}</span></td>
                        <td><Link :href="`/organizacoes/${item.id}/editar`">{{ podeAdministrar ? 'Editar' : 'Visualizar' }}</Link></td>
                    </tr>
                    <tr v-if="organizacoes.data.length === 0"><td colspan="5" class="estado-vazio">Nenhuma organização encontrada.</td></tr>
                </tbody>
            </table>
            <nav v-if="organizacoes.links.length > 3" class="paginacao">
                <Link v-for="link in organizacoes.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="{ ativo: link.active, desabilitado: !link.url }" />
            </nav>
        </section>
    </AppShell>
</template>
