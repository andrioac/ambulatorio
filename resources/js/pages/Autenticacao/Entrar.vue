<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppBrand from '../../components/AppBrand.vue';
import FormField from '../../components/FormField.vue';

const formulario = useForm({ email: '', password: '', remember: false });
const enviar = () => formulario.post('/entrar', { onFinish: () => formulario.reset('password') });
</script>

<template>
    <Head title="Entrar" />
    <div class="auth-layout">
        <header class="auth-header">
            <AppBrand />
            <span class="auth-header__seguranca">Ambiente seguro</span>
        </header>
        <main class="auth-page">
            <section class="auth-hero">
                <img src="/brand/logo-mark.svg" alt="" />
                <h1>Ambulatório<br />Inteligente</h1>
                <p class="auth-hero__slogan">Cuidado e Atendimento Humanizado</p>
                <p>Um sistema integrado para promover cuidado, acolhimento e eficiência no atendimento em saúde.</p>
                <strong>Humanizar é o que nos conecta.</strong>
                <div class="auth-beneficios">
                    <span>♡ Atendimento humanizado</span><span>♢ Segurança e privacidade</span><span>▥ Gestão inteligente</span>
                </div>
            </section>
            <form class="auth-card" @submit.prevent="enviar">
                <div class="auth-card__icone">♙</div>
                <h2>Bem-vindo(a) de volta!</h2>
                <p>Faça login para acessar o sistema</p>
                <FormField id="email" v-model="formulario.email" rotulo="E-mail" tipo="email" autocomplete="username" :erro="formulario.errors.email" obrigatorio />
                <FormField id="password" v-model="formulario.password" rotulo="Senha" tipo="password" autocomplete="current-password" :erro="formulario.errors.password" obrigatorio />
                <label class="auth-lembrar"><input v-model="formulario.remember" type="checkbox" /> Lembrar meu acesso</label>
                <button class="botao botao--primario botao--largo" type="submit" :disabled="formulario.processing">{{ formulario.processing ? 'Entrando…' : 'Entrar' }}</button>
                <p class="auth-card__nota">Seus dados são protegidos com segurança e acesso controlado.</p>
            </form>
        </main>
    </div>
</template>
