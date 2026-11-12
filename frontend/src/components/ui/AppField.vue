<script setup lang="ts">
import { Check } from '@lucide/vue'

const props = defineProps<{
  id: string
  label: string
  type?: string
  autocomplete?: string
  dica?: string
  erro?: string
  // Campo conferido e correto: borda verde e ícone de confirmação
  valido?: boolean
  placeholder?: string
  maxlength?: number
  // Teclado do celular (ex.: "numeric" abre só números)
  inputmode?: 'text' | 'numeric' | 'decimal' | 'email'
  // Texto fixo dentro do campo, à esquerda (ex.: "R$")
  prefixo?: string
  // Transforma o texto a cada digitação (ex.: máscara de valor em reais)
  mascara?: (texto: string) => string
}>()

const emit = defineEmits<{ sair: [] }>()

const valor = defineModel<string>({ required: true })

function aoDigitar(evento: Event): void {
  const campo = evento.target as HTMLInputElement
  const texto = props.mascara ? props.mascara(campo.value) : campo.value

  // Grava direto no elemento: se o texto final for igual ao anterior, o Vue não repinta o campo
  // e a letra digitada ficaria na tela mesmo com o estado correto
  campo.value = texto
  valor.value = texto
}
</script>

<template>
  <div>
    <label :for="id" class="block text-sm font-medium text-slate-700">{{ label }}</label>
    <div class="relative mt-1">
      <span
        v-if="prefixo"
        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-500"
      >
        {{ prefixo }}
      </span>
      <input
        :id="id"
        :value="valor"
        :type="type ?? 'text'"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        :maxlength="maxlength"
        :inputmode="inputmode"
        :aria-invalid="erro ? 'true' : undefined"
        class="campo"
        :class="[
          erro ? 'border-red-500' : valido ? 'border-emerald-500' : '',
          prefixo ? 'h-11 pl-10 text-lg' : '',
          $slots.sufixo || valido ? 'pr-10' : '',
        ]"
        @input="aoDigitar"
        @blur="emit('sair')"
      />
      <Check
        v-if="valido && !erro && !$slots.sufixo"
        class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-emerald-600"
        aria-hidden="true"
      />
      <!-- Conteúdo extra à direita do campo (ex.: botão do calendário) -->
      <slot name="sufixo" />
    </div>
    <p v-if="erro" class="mt-1 text-sm text-red-600">{{ erro }}</p>
    <p v-else-if="dica" class="mt-1 text-sm text-slate-500">{{ dica }}</p>
  </div>
</template>
