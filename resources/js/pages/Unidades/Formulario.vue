<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';

const props = withDefaults(defineProps<{ unidade?: any; organizacoes: Array<any>; podeAdministrar?: boolean }>(), { podeAdministrar: true });
const edicao = Boolean(props.unidade?.id);
const form = useForm({
    organizacao_saude_id: props.unidade?.organizacao_saude_id ?? '',
    nome: props.unidade?.nome ?? '',
    cnes: props.unidade?.cnes ?? '',
    tipo: props.unidade?.tipo ?? 'ubs',
    ativo: props.unidade?.ativo ?? true,
});
const salvar = () => edicao ? form.put(`/unidades/${props.unidade.id}`) : form.post('/unidades');
</script>

<template>
    <Head :title="edicao ? 'Editar unidade' : 'Nova unidade'" />
    <AppShell :titulo="edicao ? 'Editar unidade' : 'Nova unidade'">
        <div class="pagina-acoes"><Link href="/unidades">← Voltar para unidades</Link></div>
        <form class="form-card" @submit.prevent="salvar">
            <div class="form-card__cabecalho"><div><p class="eyebrow">Estrutura operacional</p><h2>Dados da unidade</h2></div><label class="campo-check"><input v-model="form.ativo" type="checkbox" :disabled="!podeAdministrar" /> Unidade ativa</label></div>
            <div class="form-grid">
                <label class="campo-formulario form-grid--duplo"><span class="campo-formulario__rotulo">Nome *</span><input v-model="form.nome" class="campo-formulario__controle" required :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.nome }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Organização *</span><select v-model="form.organizacao_saude_id" class="campo-formulario__controle" required :disabled="!podeAdministrar"><option value="">Selecione</option><option v-for="o in organizacoes" :key="o.id" :value="o.id">{{ o.nome }}{{ o.ativo === false ? ' (inativa)' : '' }}</option></select><small class="campo-formulario__erro">{{ form.errors.organizacao_saude_id }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">CNES</span><input v-model="form.cnes" class="campo-formulario__controle" inputmode="numeric" maxlength="7" :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.cnes }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Tipo *</span><select v-model="form.tipo" class="campo-formulario__controle" :disabled="!podeAdministrar"><option value="ubs">UBS</option><option value="ambulatorio">Ambulatório</option><option value="centro_especialidades">Centro de especialidades</option><option value="outro">Outro</option></select><small class="campo-formulario__erro">{{ form.errors.tipo }}</small></label>
            </div>
            <div v-if="podeAdministrar" class="form-acoes"><button class="botao botao--primario" :disabled="form.processing">Salvar unidade</button></div>
        </form>
    </AppShell>
</template>
