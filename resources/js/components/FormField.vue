<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        id: string;
        rotulo: string;
        tipo?: string;
        modelValue: string;
        autocomplete?: string;
        obrigatorio?: boolean;
        erro?: string;
        ajuda?: string;
        minlength?: number;
        maxlength?: number;
        inputmode?: 'none' | 'text' | 'decimal' | 'numeric' | 'tel' | 'search' | 'email' | 'url';
        pattern?: string;
        autocapitalize?: string;
        spellcheck?: boolean;
        desabilitado?: boolean;
        formatador?: (valor: string) => string;
    }>(),
    {
        tipo: 'text',
        autocomplete: undefined,
        obrigatorio: false,
        erro: undefined,
        ajuda: undefined,
        minlength: undefined,
        maxlength: undefined,
        inputmode: undefined,
        pattern: undefined,
        autocapitalize: undefined,
        spellcheck: undefined,
        desabilitado: false,
        formatador: undefined,
    },
);

const emit = defineEmits<{ 'update:modelValue': [valor: string] }>();
const descricaoId = computed(() => (props.erro || props.ajuda ? `${props.id}-descricao` : undefined));

const atualizarValor = (evento: Event) => {
    const controle = evento.target as HTMLInputElement;
    const valor = props.formatador ? props.formatador(controle.value) : controle.value;

    if (controle.value !== valor) {
        controle.value = valor;
    }

    emit('update:modelValue', valor);
};
</script>

<template>
    <div class="campo-formulario">
        <label class="campo-formulario__rotulo" :for="id">{{ rotulo }}</label>
        <input
            :id="id"
            class="campo-formulario__controle"
            :class="{ 'campo-formulario__controle--erro': erro }"
            :type="tipo"
            :value="modelValue"
            :autocomplete="autocomplete"
            :required="obrigatorio"
            :disabled="desabilitado"
            :aria-invalid="Boolean(erro)"
            :aria-describedby="descricaoId"
            :minlength="minlength"
            :maxlength="maxlength"
            :inputmode="inputmode"
            :pattern="pattern"
            :autocapitalize="autocapitalize"
            :spellcheck="spellcheck"
            @input="atualizarValor"
        />
        <p v-if="erro" :id="descricaoId" class="campo-formulario__erro" role="alert">{{ erro }}</p>
        <p v-else-if="ajuda" :id="descricaoId" class="campo-formulario__ajuda">{{ ajuda }}</p>
    </div>
</template>
