<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppBrand from '../../components/AppBrand.vue';
import FormField from '../../components/FormField.vue';

const page = usePage<{ flash?: { status?: string } }>();
const formulario = useForm({ email: '' });
const enviar = () => formulario.post('/esqueci-minha-senha');
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
                <button class="botao botao--primario botao--largo" type="submit" :disabled="formulario.processing">
                    {{ formulario.processing ? 'Enviando…' : 'Enviar instruções' }}
                </button>
                <p class="auth-card__nota"><Link href="/entrar">Voltar para o login</Link></p>
            </form>
        </main>
    </div>
</template>
