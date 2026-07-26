<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppBrand from './AppBrand.vue';

defineProps<{ titulo?: string }>();

interface ItemMenu {
    rotulo: string;
    href: string;
    permissao?: string;
    disponivel: boolean;
}

const page = usePage<{
    auth: {
        usuario: { id: number; name: string; email: string } | null;
        capacidades: Record<string, boolean>;
    };
}>();

const menuAberto = ref(false);
const sair = () => router.post('/sair');
const ativo = (prefixo: string) => page.url === prefixo || page.url.startsWith(`${prefixo}/`);
const capacidades = computed(() => page.props.auth?.capacidades ?? {});

const grupos: Array<{ titulo: string; itens: ItemMenu[] }> = [
    {
        titulo: 'Atendimento',
        itens: [
            { rotulo: 'Pacientes', href: '/pacientes', permissao: 'pacientes.visualizar', disponivel: false },
            { rotulo: 'Recepção e fila', href: '/fila', permissao: 'fila.visualizar', disponivel: false },
            { rotulo: 'Triagem', href: '/triagem', permissao: 'triagem.realizar', disponivel: false },
            { rotulo: 'Prontuário', href: '/prontuario', permissao: 'prontuario.visualizar', disponivel: false },
            { rotulo: 'Documentos clínicos', href: '/documentos-clinicos', permissao: 'prescricao.emitir', disponivel: false },
        ],
    },
    {
        titulo: 'Administração',
        itens: [
            { rotulo: 'Organizações', href: '/organizacoes', permissao: 'organizacoes.visualizar', disponivel: true },
            { rotulo: 'Unidades de saúde', href: '/unidades', permissao: 'unidades.visualizar', disponivel: true },
            { rotulo: 'Profissionais', href: '/profissionais', permissao: 'profissionais.visualizar', disponivel: true },
            { rotulo: 'Usuários e acessos', href: '/usuarios', permissao: 'usuarios.visualizar', disponivel: true },
            { rotulo: 'Perfis e permissões', href: '/perfis', permissao: 'perfis.visualizar', disponivel: true },
            { rotulo: 'Salas e equipes', href: '/estrutura', permissao: 'unidades.visualizar', disponivel: false },
            { rotulo: 'Auditoria', href: '/auditoria', permissao: 'auditoria.visualizar', disponivel: true },
        ],
    },
    {
        titulo: 'Gestão',
        itens: [
            { rotulo: 'Relatórios operacionais', href: '/relatorios', permissao: 'auditoria.visualizar', disponivel: false },
        ],
    },
];

const podeVer = (item: ItemMenu) => !item.permissao || Boolean(capacidades.value[item.permissao]);
const fecharMenu = () => { menuAberto.value = false; };
</script>

<template>
    <div class="app-shell">
        <button v-if="menuAberto" class="app-sidebar__overlay" type="button" aria-label="Fechar menu" @click="fecharMenu"></button>
        <aside class="app-sidebar" :class="{ 'app-sidebar--aberta': menuAberto }">
            <div class="app-sidebar__cabecalho">
                <AppBrand clara />
                <button class="app-sidebar__fechar" type="button" aria-label="Fechar menu" @click="fecharMenu">×</button>
            </div>
            <nav class="app-nav" aria-label="Navegação principal">
                <Link href="/" class="app-nav__item" :class="{ 'app-nav__item--ativo': page.url === '/' }" @click="fecharMenu">Visão geral</Link>
                <template v-for="grupo in grupos" :key="grupo.titulo">
                    <span v-if="grupo.itens.some(podeVer)" class="app-nav__grupo">{{ grupo.titulo }}</span>
                    <template v-for="item in grupo.itens" :key="item.href">
                        <Link
                            v-if="podeVer(item) && item.disponivel"
                            :href="item.href"
                            class="app-nav__item"
                            :class="{ 'app-nav__item--ativo': ativo(item.href) }"
                            @click="fecharMenu"
                        >{{ item.rotulo }}</Link>
                        <span v-else-if="podeVer(item)" class="app-nav__item app-nav__item--indisponivel">
                            {{ item.rotulo }} <small>Em breve</small>
                        </span>
                    </template>
                </template>
            </nav>
            <div class="app-sidebar__rodape">
                <span>Ambiente protegido</span>
                <small>Dados sensíveis com acesso controlado</small>
            </div>
        </aside>

        <div class="app-shell__corpo">
            <header class="app-header">
                <div class="app-header__identificacao">
                    <button class="app-header__menu" type="button" aria-label="Abrir menu" @click="menuAberto = true">☰</button>
                    <div>
                        <p class="app-header__contexto">Ambulatório Inteligente</p>
                        <h1>{{ titulo ?? 'Visão geral' }}</h1>
                    </div>
                </div>
                <div class="app-header__acoes">
                    <span class="app-header__usuario">{{ page.props.auth?.usuario?.name }}</span>
                    <span class="app-header__seguranca">Ambiente seguro</span>
                    <button class="botao botao--secundario" type="button" @click="sair">Sair</button>
                </div>
            </header>
            <main class="app-content">
                <div v-if="page.props.flash?.sucesso" class="mensagem mensagem--sucesso">{{ page.props.flash.sucesso }}</div>
                <slot />
            </main>
        </div>
    </div>
</template>
