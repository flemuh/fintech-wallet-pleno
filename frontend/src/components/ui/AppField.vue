<script setup lang="ts">
const props = defineProps<{
  id: string
  label: string
  type?: string
  autocomplete?: string
  dica?: string
  erro?: string
  // Transforma o texto a cada digitação (ex.: máscara de valor em reais)
  mascara?: (texto: string) => string
}>()

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
    <input
      :id="id"
      :value="valor"
      :type="type ?? 'text'"
      :autocomplete="autocomplete"
      :aria-invalid="erro ? 'true' : undefined"
      class="mt-1 block w-full rounded border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      :class="erro ? 'border-red-500' : 'border-slate-300'"
      @input="aoDigitar"
    />
    <p v-if="erro" class="mt-1 text-sm text-red-600">{{ erro }}</p>
    <p v-else-if="dica" class="mt-1 text-sm text-slate-500">{{ dica }}</p>
  </div>
</template>
