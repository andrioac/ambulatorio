<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '../components/AppShell.vue';

const logoUrl = '/brand/logo-mark.svg';
</script>

<template>
    <Head title="Visão geral" />
    <AppShell titulo="Visão geral">
        <section class="painel-boas-vindas">
            <div>
                <p class="eyebrow">Cuidado que organiza. Tecnologia que acolhe.</p>
                <h2>Bem-vindo ao Ambulatório Inteligente</h2>
                <p>A fundação segura do sistema está pronta para receber os fluxos de pacientes, fila, triagem e prontuário.</p>
            </div>
            <img :src="logoUrl" alt="" />
        </section>

        <section class="painel-grid" aria-label="Módulos planejados">
            <article class="painel-card painel-card--destaque">
                <span class="painel-card__icone">♡</span>
                <h3>Atendimento humanizado</h3>
                <p>Recepção, acolhimento e acompanhamento do paciente em um fluxo único.</p>
                <span class="painel-card__estado">Em preparação</span>
            </article>
            <article class="painel-card">
                <span class="painel-card__icone">⌁</span>
                <h3>Segurança e privacidade</h3>
                <p>Autenticação, autorização escopada e auditoria imutável desde a fundação.</p>
                <span class="painel-card__estado painel-card__estado--ativo">Fundação ativa</span>
            </article>
            <article class="painel-card">
                <span class="painel-card__icone">▥</span>
                <h3>Gestão inteligente</h3>
                <p>Organizações e unidades estruturadas para evoluir com indicadores confiáveis.</p>
                <span class="painel-card__estado">Em preparação</span>
            </article>
        </section>

        <section class="painel-proximos">
            <div>
                <p class="eyebrow">Próxima entrega</p>
                <h2>Profissionais e vínculos com unidades</h2>
                <p>Cadastro separado entre identidade de acesso e atuação profissional, incluindo CNS, CBO e conselho.</p>
            </div>
            <span class="painel-proximos__selo">S05</span>
        </section>
    </AppShell>
</template>
