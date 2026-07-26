<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    profissionais: { data: Array<any>; links: Array<any>; total: number };
    filtros: { busca?: string; situacao?: string };
    podeAdministrar: boolean;
}>();

const filtros = reactive({ busca: props.filtros.busca ?? '', situacao: props.filtros.situacao ?? '' });
const pesquisar = () => router.get('/profissionais', filtros, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Profissionais" />
    <AppShell titulo="Profissionais">
        <section class="pagina-cabecalho">
            <div><p class="eyebrow">Equipe assistencial</p><h2>Profissionais e vínculos</h2><p>Cadastre identidades profissionais e acompanhe a atuação em cada unidade.</p></div>
            <Link v-if="podeAdministrar" class="botao botao--primario" href="/profissionais/novo">Novo profissional</Link>
        </section>

        <form class="filtros-card" @submit.prevent="pesquisar">
            <input v-model="filtros.busca" class="campo-formulario__controle" placeholder="Nome, CPF, CNS ou categoria" />
            <select v-model="filtros.situacao" class="campo-formulario__controle"><option value="">Todas as situações</option><option value="ativos">Ativos</option><option value="inativos">Inativos</option></select>
            <button class="botao botao--secundario" type="submit">Pesquisar</button>
        </form>

        <section class="tabela-card">
            <table class="tabela-dados">
                <thead><tr><th>Profissional</th><th>Categoria</th><th>Conselho</th><th>Vínculos ativos</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in profissionais.data" :key="item.id">
                        <td><strong>{{ item.nome }}</strong><small>{{ item.cpf || item.cns || 'Sem documento informado' }}</small></td>
                        <td>{{ item.categoria }}</td>
                        <td>{{ item.conselho_tipo ? `${item.conselho_tipo} ${item.conselho_numero ?? ''}/${item.conselho_uf ?? ''}` : '—' }}</td>
                        <td>{{ item.vinculos_ativos_count }}</td>
                        <td><span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativo' : 'Inativo' }}</span></td>
                        <td><Link :href="`/profissionais/${item.id}/editar`">{{ podeAdministrar ? 'Editar' : 'Visualizar' }}</Link></td>
                    </tr>
                    <tr v-if="profissionais.data.length === 0"><td colspan="6" class="estado-vazio">Nenhum profissional encontrado.</td></tr>
                </tbody>
            </table>
            <nav v-if="profissionais.links.length > 3" class="paginacao"><Link v-for="link in profissionais.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="{ ativo: link.active, desabilitado: !link.url }" /></nav>
        </section>
    </AppShell>
</template>
