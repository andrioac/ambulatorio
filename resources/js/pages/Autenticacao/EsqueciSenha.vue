<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';
import AppBrand from '../../components/AppBrand.vue';
import FormField from '../../components/FormField.vue';

const page = usePage<{ flash?: { status?: string } }>();
const formulario = useForm({ email: '' });
const segundosRestantes = ref(0);
let temporizador: ReturnType<typeof setInterval> | null = null;

const iniciarEspera = () => {
    segundosRestantes.value = 60;
    if (temporizador) clearInterval(temporizador);
    temporizador = setInterval(() => {
        segundosRestantes.value -= 1;
        if (segundosRestantes.value <= 0 && temporizador) {
            clearInterval(temporizador);
            temporizador = null;
        }
    }, 1000);
};

const enviar = () => formulario.post('/esqueci-minha-senha', {
    preserveScroll: true,
    onSuccess: iniciarEspera,
});

onBeforeUnmount(() => {
    if (temporizador) clearInterval(temporizador);
});
</script>

<template>
    <Head title="Recuperar senha" />
    <div class="auth-layout">
        <header class="auth-header">
            <AppBrand />
            <span class="auth-header__seguranca">Ambiente seguro</span>
        </header>
        <main class="auth-page auth-page--centralizada">
            <form class="auth-card auth-card--compacta" @submit.prevent="enviar">
                <div class="auth-card__icone">✉</div>
                <h2>Recuperar senha</h2>
                <p>Informe seu e-mail para receber as instruções de redefinição.</p>
                <div v-if="page.props.flash?.status" class="mensagem mensagem--sucesso">{{ page.props.flash.status }}</div>
                <FormField id="email" v-model="formulario.email" rotulo="E-mail" tipo="email" autocomplete="email" :erro="formulario.errors.email" obrigatorio />
                <button class="botao botao--primario botao--largo" type="submit" :disabled="formulario.processing || segundosRestantes > 0">
                    <template v-if="formulario.processing">Processando…</template>
                    <template v-else-if="segundosRestantes > 0">Tentar novamente em {{ segundosRestantes }}s</template>
                    <template v-else>Enviar instruções</template>
                </button>
                <p class="auth-card__nota"><Link href="/entrar">Voltar para o login</Link></p>
            </form>
        </main>
    </div>
</template>
