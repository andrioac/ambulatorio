<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    usuarios: { data: Array<any>; links: Array<any>; total: number };
    filtros: { busca?: string; situacao?: string };
    podeAdministrar: boolean;
}>();
const filtros = reactive({ busca: props.filtros.busca ?? '', situacao: props.filtros.situacao ?? '' });
const pesquisar = () => router.get('/usuarios', filtros, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Usuários e acessos" />
    <AppShell titulo="Usuários e acessos">
        <section class="pagina-cabecalho">
            <div><p class="eyebrow">Identidade e autorização</p><h2>Usuários do sistema</h2><p>Gerencie identidades de acesso e atribuições de perfil por escopo.</p></div>
            <Link v-if="podeAdministrar" class="botao botao--primario" href="/usuarios/novo">Novo usuário</Link>
        </section>
        <form class="filtros-card" @submit.prevent="pesquisar">
            <input v-model="filtros.busca" class="campo-formulario__controle" placeholder="Nome ou e-mail" />
            <select v-model="filtros.situacao" class="campo-formulario__controle"><option value="">Todas as situações</option><option value="ativos">Ativos</option><option value="inativos">Inativos</option></select>
            <button class="botao botao--secundario">Pesquisar</button>
        </form>
        <section class="tabela-card">
            <table class="tabela-dados">
                <thead><tr><th>Usuário</th><th>Profissional associado</th><th>Atribuições ativas</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in usuarios.data" :key="item.id">
                        <td><strong>{{ item.name }}</strong><small>{{ item.email }}</small></td>
                        <td>{{ item.profissional ? `${item.profissional.nome} — ${item.profissional.categoria}` : '—' }}</td>
                        <td>{{ item.atribuicoes_ativas_count }}</td>
                        <td><span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativo' : 'Inativo' }}</span></td>
                        <td><Link :href="`/usuarios/${item.id}/editar`">{{ podeAdministrar ? 'Editar' : 'Visualizar' }}</Link></td>
                    </tr>
                    <tr v-if="usuarios.data.length === 0"><td colspan="5" class="estado-vazio">Nenhum usuário encontrado.</td></tr>
                </tbody>
            </table>
            <nav v-if="usuarios.links.length > 3" class="paginacao"><Link v-for="link in usuarios.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="{ ativo: link.active, desabilitado: !link.url }" /></nav>
        </section>
    </AppShell>
</template>
