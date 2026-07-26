<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import AppShell from '../../components/AppShell.vue';
import { formatarCns, formatarCpf } from '../../utils/formatadoresCampos';

const props = defineProps<{
    profissional?: any;
    usuarios: Array<any>;
    unidades: Array<any>;
    podeAdministrar: boolean;
    exigeVinculoInicial: boolean;
}>();
const edicao = Boolean(props.profissional?.id);
const form = useForm({
    user_id: props.profissional?.user_id ?? '', nome: props.profissional?.nome ?? '', cpf: formatarCpf(props.profissional?.cpf ?? ''),
    cns: formatarCns(props.profissional?.cns ?? ''), categoria: props.profissional?.categoria ?? '', cbo: props.profissional?.cbo ?? '',
    conselho_tipo: props.profissional?.conselho_tipo ?? '', conselho_numero: props.profissional?.conselho_numero ?? '',
    conselho_uf: props.profissional?.conselho_uf ?? '', ativo: props.profissional?.ativo ?? true, unidade_saude_id_inicial: '',
});
const vinculo = useForm({ unidade_saude_id: '', vigente_de: '', vigente_ate: '' });
const edicoes = reactive<Record<number, { unidade_saude_id: number | string; vigente_de: string; vigente_ate: string }>>({});

watch(
    () => props.profissional?.vinculos_unidades,
    (itens: Array<any> = []) => {
        for (const item of itens) {
            edicoes[item.id] = {
                unidade_saude_id: item.unidade_saude_id,
                vigente_de: item.vigente_de ?? '',
                vigente_ate: item.vigente_ate ?? '',
            };
        }
    },
    { immediate: true, deep: true },
);

const salvar = () => edicao ? form.put(`/profissionais/${props.profissional.id}`) : form.post('/profissionais');
const salvarVinculo = () => vinculo.post(`/profissionais/${props.profissional.id}/vinculos`, { preserveScroll: true, onSuccess: () => vinculo.reset() });
const atualizarVinculo = (id: number) => router.put(`/profissionais/${props.profissional.id}/vinculos/${id}`, edicoes[id], { preserveScroll: true });
const desativarVinculo = (id: number) => router.delete(`/profissionais/${props.profissional.id}/vinculos/${id}`, { preserveScroll: true });
const reativarVinculo = (id: number) => router.patch(`/profissionais/${props.profissional.id}/vinculos/${id}/reativar`, {}, { preserveScroll: true });
const atualizarCpf = (evento: Event) => { form.cpf = formatarCpf((evento.target as HTMLInputElement).value); };
const atualizarCns = (evento: Event) => { form.cns = formatarCns((evento.target as HTMLInputElement).value); };
</script>

<template>
    <Head :title="edicao ? 'Editar profissional' : 'Novo profissional'" />
    <AppShell :titulo="edicao ? 'Editar profissional' : 'Novo profissional'">
        <div class="pagina-acoes"><Link href="/profissionais">← Voltar para profissionais</Link></div>
        <form class="form-card" @submit.prevent="salvar">
            <div class="form-card__cabecalho"><div><p class="eyebrow">Identidade profissional</p><h2>Dados cadastrais</h2></div><label class="campo-check"><input v-model="form.ativo" type="checkbox" :disabled="!podeAdministrar" /> Profissional ativo</label></div>
            <div class="form-grid">
                <label class="campo-formulario form-grid--duplo"><span class="campo-formulario__rotulo">Nome *</span><input v-model="form.nome" class="campo-formulario__controle" required :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.nome }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Categoria *</span><input v-model="form.categoria" class="campo-formulario__controle" placeholder="Ex.: medicina" required :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.categoria }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Usuário de acesso</span><select v-model="form.user_id" class="campo-formulario__controle" :disabled="!podeAdministrar"><option value="">Sem usuário associado</option><option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }} — {{ u.email }}</option></select><small class="campo-formulario__erro">{{ form.errors.user_id }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">CPF</span><input :value="form.cpf" class="campo-formulario__controle" inputmode="numeric" maxlength="14" :disabled="!podeAdministrar" @input="atualizarCpf" /><small class="campo-formulario__erro">{{ form.errors.cpf }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">CNS</span><input :value="form.cns" class="campo-formulario__controle" inputmode="numeric" maxlength="18" :disabled="!podeAdministrar" @input="atualizarCns" /><small class="campo-formulario__erro">{{ form.errors.cns }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">CBO</span><input v-model="form.cbo" class="campo-formulario__controle" inputmode="numeric" :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.cbo }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Conselho</span><input v-model="form.conselho_tipo" class="campo-formulario__controle" placeholder="CRM, COREN..." :disabled="!podeAdministrar" /></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Número</span><input v-model="form.conselho_numero" class="campo-formulario__controle" :disabled="!podeAdministrar" /></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">UF</span><input v-model="form.conselho_uf" maxlength="2" class="campo-formulario__controle" :disabled="!podeAdministrar" /></label>
                <label v-if="!edicao" class="campo-formulario form-grid--duplo"><span class="campo-formulario__rotulo">Unidade inicial {{ exigeVinculoInicial ? '*' : '' }}</span><select v-model="form.unidade_saude_id_inicial" class="campo-formulario__controle" :required="exigeVinculoInicial"><option value="">{{ exigeVinculoInicial ? 'Selecione' : 'Vincular depois' }}</option><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }} — {{ u.organizacao.nome }}</option></select><small class="campo-formulario__erro">{{ form.errors.unidade_saude_id_inicial || form.errors.unidade_saude_id }}</small></label>
            </div>
            <div v-if="podeAdministrar" class="form-acoes"><button class="botao botao--primario" :disabled="form.processing">Salvar profissional</button></div>
        </form>

        <section v-if="edicao" class="form-card">
            <div class="form-card__cabecalho"><div><p class="eyebrow">Lotação</p><h2>Vínculos com unidades</h2></div></div>
            <form v-if="podeAdministrar" class="vinculo-form" @submit.prevent="salvarVinculo">
                <select v-model="vinculo.unidade_saude_id" class="campo-formulario__controle" required><option value="">Selecione a unidade</option><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }} — {{ u.organizacao.nome }}</option></select>
                <input v-model="vinculo.vigente_de" type="date" class="campo-formulario__controle" aria-label="Vigente de" />
                <input v-model="vinculo.vigente_ate" type="date" class="campo-formulario__controle" aria-label="Vigente até" />
                <button class="botao botao--secundario">Adicionar vínculo</button>
            </form>
            <small class="campo-formulario__erro">{{ vinculo.errors.vigente_ate || vinculo.errors.unidade_saude_id }}</small>
            <div class="lista-vinculos">
                <article v-for="item in profissional.vinculos_unidades" :key="item.id" class="vinculo-item vinculo-item--editavel">
                    <div><strong>{{ item.unidade.nome }}</strong><small>{{ item.unidade.organizacao.nome }} · {{ item.vigente_de || 'Sem início' }} até {{ item.vigente_ate || 'Sem término' }}</small></div>
                    <span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativo' : 'Inativo' }}</span>
                    <div v-if="podeAdministrar" class="acoes-inline"><button v-if="item.ativo" class="botao botao--perigo" type="button" @click="desativarVinculo(item.id)">Desativar</button><button v-else class="botao botao--secundario" type="button" @click="reativarVinculo(item.id)">Reativar</button></div>
                    <details v-if="podeAdministrar && edicoes[item.id]" class="vinculo-edicao"><summary>Editar vínculo</summary><form class="vinculo-form vinculo-form--interno" @submit.prevent="atualizarVinculo(item.id)"><select v-model="edicoes[item.id].unidade_saude_id" class="campo-formulario__controle" required><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }} — {{ u.organizacao.nome }}</option></select><input v-model="edicoes[item.id].vigente_de" type="date" class="campo-formulario__controle" /><input v-model="edicoes[item.id].vigente_ate" type="date" class="campo-formulario__controle" /><button class="botao botao--secundario">Atualizar</button></form></details>
                </article>
                <p v-if="profissional.vinculos_unidades.length === 0" class="estado-vazio">Nenhum vínculo cadastrado.</p>
            </div>
        </section>
    </AppShell>
</template>
