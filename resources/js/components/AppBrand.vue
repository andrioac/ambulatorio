<script setup lang="ts">
withDefaults(defineProps<{ compacta?: boolean; clara?: boolean }>(), { compacta: false, clara: false });

const logoUrl = '/brand/logo-mark.svg';
</script>

<template>
    <div class="app-brand" :class="{ 'app-brand--compacta': compacta, 'app-brand--clara': clara }">
        <img class="app-brand__marca" :src="logoUrl" alt="" />
        <div v-if="!compacta" class="app-brand__texto">
            <strong>Ambulatório Inteligente</strong>
            <span>Cuidado e Atendimento Humanizado</span>
        </div>
    </div>
</template>
