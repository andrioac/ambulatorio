<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppShell from '../../components/AppShell.vue';
import { formatarCnpj } from '../../utils/formatadoresCampos';

const props = withDefaults(defineProps<{ organizacao?: any; podeAdministrar?: boolean }>(), { podeAdministrar: true });
const edicao = Boolean(props.organizacao?.id);
const form = useForm({
    nome: props.organizacao?.nome ?? '',
    sigla: props.organizacao?.sigla ?? '',
    cnpj: formatarCnpj(props.organizacao?.cnpj ?? ''),
    ativo: props.organizacao?.ativo ?? true,
});
const salvar = () => edicao ? form.put(`/organizacoes/${props.organizacao.id}`) : form.post('/organizacoes');
</script>

<template>
    <Head :title="edicao ? 'Editar organização' : 'Nova organização'" />
    <AppShell :titulo="edicao ? 'Editar organização' : 'Nova organização'">
        <div class="pagina-acoes"><Link href="/organizacoes">← Voltar para organizações</Link></div>
        <form class="form-card" @submit.prevent="salvar">
            <div class="form-card__cabecalho">
                <div><p class="eyebrow">Estrutura institucional</p><h2>Dados da organização</h2></div>
                <label class="campo-check"><input v-model="form.ativo" type="checkbox" :disabled="!podeAdministrar" /> Organização ativa</label>
            </div>
            <div class="form-grid">
                <label class="campo-formulario form-grid--duplo"><span class="campo-formulario__rotulo">Nome *</span><input v-model="form.nome" class="campo-formulario__controle" :disabled="!podeAdministrar" required /><small class="campo-formulario__erro">{{ form.errors.nome }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Sigla</span><input v-model="form.sigla" class="campo-formulario__controle" maxlength="20" :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.sigla }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">CNPJ</span><input :value="form.cnpj" class="campo-formulario__controle" inputmode="numeric" maxlength="18" :disabled="!podeAdministrar" @input="form.cnpj = formatarCnpj(($event.target as HTMLInputElement).value)" /><small class="campo-formulario__erro">{{ form.errors.cnpj }}</small></label>
            </div>
            <div v-if="podeAdministrar" class="form-acoes"><button class="botao botao--primario" :disabled="form.processing">Salvar organização</button></div>
        </form>
    </AppShell>
</template>
