<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppBrand from '../../components/AppBrand.vue';
import FormField from '../../components/FormField.vue';

const props = defineProps<{ token: string; email: string }>();
const formulario = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});
const enviar = () => formulario.post('/redefinir-senha', { onFinish: () => formulario.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Redefinir senha" />
    <div class="auth-layout">
        <header class="auth-header">
            <AppBrand />
            <span class="auth-header__seguranca">Ambiente seguro</span>
        </header>
        <main class="auth-page auth-page--centralizada">
            <form class="auth-card auth-card--compacta" @submit.prevent="enviar">
                <div class="auth-card__icone">♢</div>
                <h2>Crie uma nova senha</h2>
                <p>Use uma senha forte e diferente das utilizadas anteriormente.</p>
                <FormField id="email" v-model="formulario.email" rotulo="E-mail" tipo="email" autocomplete="username" :erro="formulario.errors.email" obrigatorio />
                <FormField id="password" v-model="formulario.password" rotulo="Nova senha" tipo="password" autocomplete="new-password" :erro="formulario.errors.password" obrigatorio />
                <FormField id="password_confirmation" v-model="formulario.password_confirmation" rotulo="Confirmar nova senha" tipo="password" autocomplete="new-password" :erro="formulario.errors.password_confirmation" obrigatorio />
                <button class="botao botao--primario botao--largo" type="submit" :disabled="formulario.processing">
                    {{ formulario.processing ? 'Salvando…' : 'Redefinir senha' }}
                </button>
                <p class="auth-card__nota"><Link href="/entrar">Voltar para o login</Link></p>
            </form>
        </main>
    </div>
</template>
