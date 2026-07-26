<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppShell from '../components/AppShell.vue';

const logoUrl = '/brand/logo-mark.svg';
const page = usePage<{ auth: { capacidades: Record<string, boolean> } }>();
const pode = (chave: string) => Boolean(page.props.auth?.capacidades?.[chave]);
</script>

<template>
    <Head title="Visão geral" />
    <AppShell titulo="Visão geral">
        <section class="painel-boas-vindas">
            <div>
                <p class="eyebrow">Cuidado que organiza. Tecnologia que acolhe.</p>
                <h2>Bem-vindo ao Ambulatório Inteligente</h2>
                <p>A fundação administrativa está estruturada para iniciar a jornada do paciente com segurança, escopo e auditoria.</p>
            </div>
            <img :src="logoUrl" alt="" />
        </section>

        <section class="painel-grid" aria-label="Módulos do sistema">
            <article class="painel-card painel-card--destaque">
                <span class="painel-card__icone">♡</span>
                <h3>Atendimento humanizado</h3>
                <p>Cadastro de pacientes, recepção, acolhimento e acompanhamento em um fluxo único.</p>
                <span class="painel-card__estado">Próxima etapa</span>
            </article>
            <article class="painel-card">
                <span class="painel-card__icone">⌁</span>
                <h3>Segurança e privacidade</h3>
                <p>Autenticação, recuperação de senha, autorização escopada e auditoria imutável.</p>
                <Link v-if="pode('auditoria.visualizar')" class="painel-card__link" href="/auditoria">Consultar auditoria</Link>
                <span v-else class="painel-card__estado painel-card__estado--ativo">Fundação ativa</span>
            </article>
            <article class="painel-card">
                <span class="painel-card__icone">▥</span>
                <h3>Gestão administrativa</h3>
                <p>Organizações, unidades, usuários, acessos, profissionais e vínculos disponíveis conforme sua permissão.</p>
                <div class="painel-card__atalhos">
                    <Link v-if="pode('unidades.visualizar')" href="/unidades">Unidades</Link>
                    <Link v-if="pode('profissionais.visualizar')" href="/profissionais">Profissionais</Link>
                    <Link v-if="pode('usuarios.visualizar')" href="/usuarios">Usuários</Link>
                </div>
            </article>
        </section>

        <section class="painel-proximos">
            <div>
                <p class="eyebrow">Próxima entrega</p>
                <h2>Cadastro e pesquisa de pacientes</h2>
                <p>Identificação segura, pesquisa por documentos e dados pessoais, prevenção de duplicidades e histórico cadastral.</p>
            </div>
            <span class="painel-proximos__selo">S06</span>
        </section>
    </AppShell>
</template>
