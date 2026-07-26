<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../components/AppShell.vue';

const props = defineProps<{
    usuario?: any;
    perfis: Array<any>;
    organizacoes: Array<any>;
    unidades: Array<any>;
    podeEscopoSistema: boolean;
    podeAdministrar: boolean;
}>();

const edicao = Boolean(props.usuario?.id);
const form = useForm({
    name: props.usuario?.name ?? '',
    email: props.usuario?.email ?? '',
    password: '',
    password_confirmation: '',
    ativo: props.usuario?.ativo ?? true,
    perfil_id: '',
    tipo_escopo: props.podeEscopoSistema ? 'sistema' : (props.organizacoes.length ? 'organizacao' : 'unidade'),
    organizacao_saude_id: '',
    unidade_saude_id: '',
    vigente_de: '',
    vigente_ate: '',
});
const atribuicao = useForm({ perfil_id: '', tipo_escopo: props.podeEscopoSistema ? 'sistema' : (props.organizacoes.length ? 'organizacao' : 'unidade'), organizacao_saude_id: '', unidade_saude_id: '', vigente_de: '', vigente_ate: '' });

const salvar = () => edicao ? form.put(`/usuarios/${props.usuario.id}`, { onSuccess: () => form.reset('password', 'password_confirmation') }) : form.post('/usuarios');
const atribuir = () => atribuicao.post(`/usuarios/${props.usuario.id}/atribuicoes`, { onSuccess: () => atribuicao.reset() });
const revogar = (id: number) => router.delete(`/usuarios/${props.usuario.id}/atribuicoes/${id}`);
const escopos = computed(() => [
    ...(props.podeEscopoSistema ? [{ valor: 'sistema', rotulo: 'Sistema' }] : []),
    ...(props.organizacoes.length ? [{ valor: 'organizacao', rotulo: 'Organização' }] : []),
    ...(props.unidades.length ? [{ valor: 'unidade', rotulo: 'Unidade' }] : []),
]);
const rotuloEscopo = (item: any) => item.tipo_escopo === 'sistema' ? 'Sistema' : item.tipo_escopo === 'organizacao' ? item.organizacao?.nome : `${item.unidade?.nome} — ${item.unidade?.organizacao?.nome ?? ''}`;
const data = (valor?: string) => valor ? new Date(valor).toLocaleDateString('pt-BR') : 'Sem limite';
</script>

<template>
    <Head :title="edicao ? 'Editar usuário' : 'Novo usuário'" />
    <AppShell :titulo="edicao ? 'Editar usuário' : 'Novo usuário'">
        <div class="pagina-acoes"><Link href="/usuarios">← Voltar para usuários</Link></div>
        <form class="form-card" @submit.prevent="salvar">
            <div class="form-card__cabecalho"><div><p class="eyebrow">Identidade de acesso</p><h2>Dados do usuário</h2></div><label class="campo-check"><input v-model="form.ativo" type="checkbox" :disabled="!podeAdministrar" /> Usuário ativo</label></div>
            <div class="form-grid">
                <label class="campo-formulario form-grid--duplo"><span class="campo-formulario__rotulo">Nome *</span><input v-model="form.name" class="campo-formulario__controle" required :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.name }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">E-mail *</span><input v-model="form.email" type="email" class="campo-formulario__controle" required :disabled="!podeAdministrar" /><small class="campo-formulario__erro">{{ form.errors.email }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">{{ edicao ? 'Nova senha' : 'Senha *' }}</span><input v-model="form.password" type="password" class="campo-formulario__controle" :required="!edicao" :disabled="!podeAdministrar" autocomplete="new-password" /><small class="campo-formulario__erro">{{ form.errors.password }}</small></label>
                <label class="campo-formulario"><span class="campo-formulario__rotulo">Confirmar senha</span><input v-model="form.password_confirmation" type="password" class="campo-formulario__controle" :required="!edicao" :disabled="!podeAdministrar" autocomplete="new-password" /></label>
            </div>

            <template v-if="!edicao">
                <hr class="form-divisor" />
                <div><p class="eyebrow">Acesso inicial</p><h2>Primeira atribuição</h2><p>Para administradores sem alcance de sistema, a atribuição inicial é obrigatória.</p></div>
                <div class="form-grid">
                    <label class="campo-formulario"><span class="campo-formulario__rotulo">Perfil</span><select v-model="form.perfil_id" class="campo-formulario__controle"><option value="">{{ podeEscopoSistema ? 'Atribuir depois' : 'Selecione' }}</option><option v-for="p in perfis" :key="p.id" :value="p.id">{{ p.nome }}</option></select><small class="campo-formulario__erro">{{ form.errors.perfil_id }}</small></label>
                    <label class="campo-formulario"><span class="campo-formulario__rotulo">Escopo</span><select v-model="form.tipo_escopo" class="campo-formulario__controle"><option v-for="e in escopos" :key="e.valor" :value="e.valor">{{ e.rotulo }}</option></select><small class="campo-formulario__erro">{{ form.errors.tipo_escopo }}</small></label>
                    <label v-if="form.tipo_escopo === 'organizacao'" class="campo-formulario"><span class="campo-formulario__rotulo">Organização</span><select v-model="form.organizacao_saude_id" class="campo-formulario__controle"><option value="">Selecione</option><option v-for="o in organizacoes" :key="o.id" :value="o.id">{{ o.nome }}</option></select><small class="campo-formulario__erro">{{ form.errors.organizacao_saude_id }}</small></label>
                    <label v-if="form.tipo_escopo === 'unidade'" class="campo-formulario"><span class="campo-formulario__rotulo">Unidade</span><select v-model="form.unidade_saude_id" class="campo-formulario__controle"><option value="">Selecione</option><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }} — {{ u.organizacao.nome }}</option></select><small class="campo-formulario__erro">{{ form.errors.unidade_saude_id }}</small></label>
                    <label class="campo-formulario"><span class="campo-formulario__rotulo">Vigente de</span><input v-model="form.vigente_de" type="date" class="campo-formulario__controle" /></label>
                    <label class="campo-formulario"><span class="campo-formulario__rotulo">Vigente até</span><input v-model="form.vigente_ate" type="date" class="campo-formulario__controle" /><small class="campo-formulario__erro">{{ form.errors.vigente_ate }}</small></label>
                </div>
            </template>
            <div v-if="podeAdministrar" class="form-acoes"><button class="botao botao--primario" :disabled="form.processing">Salvar usuário</button></div>
        </form>

        <section v-if="edicao" class="form-card">
            <div class="form-card__cabecalho"><div><p class="eyebrow">Autorização escopada</p><h2>Perfis atribuídos</h2></div></div>
            <form v-if="podeAdministrar" class="atribuicao-form" @submit.prevent="atribuir">
                <select v-model="atribuicao.perfil_id" class="campo-formulario__controle" required><option value="">Selecione o perfil</option><option v-for="p in perfis" :key="p.id" :value="p.id">{{ p.nome }}</option></select>
                <select v-model="atribuicao.tipo_escopo" class="campo-formulario__controle" required><option v-for="e in escopos" :key="e.valor" :value="e.valor">{{ e.rotulo }}</option></select>
                <select v-if="atribuicao.tipo_escopo === 'organizacao'" v-model="atribuicao.organizacao_saude_id" class="campo-formulario__controle" required><option value="">Organização</option><option v-for="o in organizacoes" :key="o.id" :value="o.id">{{ o.nome }}</option></select>
                <select v-if="atribuicao.tipo_escopo === 'unidade'" v-model="atribuicao.unidade_saude_id" class="campo-formulario__controle" required><option value="">Unidade</option><option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.nome }} — {{ u.organizacao.nome }}</option></select>
                <input v-model="atribuicao.vigente_de" type="date" class="campo-formulario__controle" aria-label="Vigente de" />
                <input v-model="atribuicao.vigente_ate" type="date" class="campo-formulario__controle" aria-label="Vigente até" />
                <button class="botao botao--secundario" :disabled="atribuicao.processing">Atribuir</button>
            </form>
            <p v-if="Object.keys(atribuicao.errors).length" class="campo-formulario__erro">{{ Object.values(atribuicao.errors)[0] }}</p>
            <div class="lista-vinculos">
                <article v-for="item in usuario.atribuicoes_perfil" :key="item.id" class="vinculo-item vinculo-item--acesso">
                    <div><strong>{{ item.perfil.nome }}</strong><small>{{ rotuloEscopo(item) }} · {{ data(item.vigente_de) }} até {{ data(item.vigente_ate) }}</small></div>
                    <span class="status" :class="item.ativo ? 'status--ativo' : 'status--inativo'">{{ item.ativo ? 'Ativa' : 'Revogada' }}</span>
                    <button v-if="podeAdministrar && item.ativo" class="botao botao--perigo" type="button" @click="revogar(item.id)">Revogar</button>
                </article>
                <p v-if="usuario.atribuicoes_perfil.length === 0" class="estado-vazio">Nenhuma atribuição cadastrada.</p>
            </div>
        </section>
    </AppShell>
</template>
