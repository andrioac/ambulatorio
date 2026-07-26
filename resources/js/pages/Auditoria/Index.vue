<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    registros: { data: Array<any>; links: Array<any>; total: number };
    filtros: Record<string, any>;
    organizacoes: Array<any>;
    unidades: Array<any>;
}>();
const filtros = reactive({
    evento: props.filtros.evento ?? '', ator: props.filtros.ator ?? '', entidade: props.filtros.entidade ?? '',
    organizacao_id: props.filtros.organizacao_id ?? '', unidade_id: props.filtros.unidade_id ?? '',
    de: props.filtros.de ?? '', ate: props.filtros.ate ?? '',
});
const pesquisar = () => router.get('/auditoria', filtros, { preserveState: true, replace: true });
const limpar = () => router.get('/auditoria');
const dataHora = (valor: string) => new Date(valor).toLocaleString('pt-BR');
const json = (valor: unknown) => valor ? JSON.stringify(valor, null, 2) : '—';
</script>

<template>
    <Head title="Auditoria" />
    <AppShell titulo="Auditoria">
        <section class="pagina-cabecalho"><div><p class="eyebrow">Trilha imutável</p><h2>Eventos de auditoria</h2><p>Consulte ações relevantes respeitando o alcance autorizado do seu usuário.</p></div></section>
        <form class="filtros-card filtros-card--auditoria" @submit.prevent="pesquisar">
            <input v-model="filtros.evento" class="campo-formulario__controle" placeholder="Evento" />
            <input v-model="filtros.ator" class="campo-formulario__controle" placeholder="Usuário ou e-mail" />
            <input v-model="filtros.entidade" class="campo-formulario__controle" placeholder="Entidade ou ID" />
            <select v-model="filtros.organizacao_id" class="campo-formulario__controle"><option value="">Todas as organizações</option><option v-for="o in organizacoes" :key="o.id" :value="o.id">{{ o.nome }}</option></select>
            <select v-model="filtros.unidade_id" class="campo-formulario__controle"><option value="">Todas as unidades</option><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }}</option></select>
            <input v-model="filtros.de" type="date" class="campo-formulario__controle" aria-label="Data inicial" />
            <input v-model="filtros.ate" type="date" class="campo-formulario__controle" aria-label="Data final" />
            <div class="acoes-inline"><button class="botao botao--secundario">Pesquisar</button><button class="botao botao--texto" type="button" @click="limpar">Limpar</button></div>
        </form>
        <section class="auditoria-lista">
            <article v-for="item in registros.data" :key="item.id" class="auditoria-item">
                <div class="auditoria-item__cabecalho">
                    <div><strong>{{ item.evento }}</strong><small>{{ dataHora(item.ocorrido_em) }} · {{ item.ator?.name || 'Sistema' }}{{ item.ator?.email ? ` (${item.ator.email})` : '' }}</small></div>
                    <span class="status status--neutro">#{{ item.id }}</span>
                </div>
                <div class="auditoria-item__metadados">
                    <span>Entidade: {{ item.entidade_tipo || '—' }} {{ item.entidade_id ? `#${item.entidade_id}` : '' }}</span>
                    <span>Organização: {{ item.organizacao?.nome || '—' }}</span>
                    <span>Unidade: {{ item.unidade?.nome || '—' }}</span>
                    <span>IP: {{ item.ip || '—' }}</span>
                </div>
                <details><summary>Ver alterações</summary><div class="auditoria-json-grid"><div><h3>Antes</h3><pre>{{ json(item.dados_anteriores) }}</pre></div><div><h3>Depois</h3><pre>{{ json(item.dados_posteriores) }}</pre></div></div></details>
            </article>
            <p v-if="registros.data.length === 0" class="estado-vazio form-card">Nenhum evento encontrado.</p>
            <nav v-if="registros.links.length > 3" class="paginacao"><Link v-for="link in registros.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="{ ativo: link.active, desabilitado: !link.url }" /></nav>
        </section>
    </AppShell>
</template>
