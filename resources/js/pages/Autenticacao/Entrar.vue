<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '../../components/FormField.vue';

const formulario = useForm({ email: '', password: '', remember: false });
const enviar = () => formulario.post('/entrar', { onFinish: () => formulario.reset('password') });
</script>

<template>
    <Head title="Entrar" />
    <main class="auth-page">
        <form class="auth-card" @submit.prevent="enviar">
            <h1>Ambulatório Inteligente</h1>
            <p>Acesse o ambiente seguro de atendimento.</p>
            <FormField id="email" v-model="formulario.email" rotulo="E-mail" tipo="email" autocomplete="username" :erro="formulario.errors.email" obrigatorio />
            <FormField id="password" v-model="formulario.password" rotulo="Senha" tipo="password" autocomplete="current-password" :erro="formulario.errors.password" obrigatorio />
            <label><input v-model="formulario.remember" type="checkbox" /> Manter conectado</label>
            <button type="submit" :disabled="formulario.processing">Entrar</button>
        </form>
    </main>
</template>
