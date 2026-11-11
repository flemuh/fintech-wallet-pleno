<script setup lang="ts">
const props = defineProps<{
  id: string
  label: string
  type?: string
  autocomplete?: string
  dica?: string
  erro?: string
  placeholder?: string
  // Texto fixo dentro do campo, à esquerda (ex.: "R$")
  prefixo?: string
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
        :aria-invalid="erro ? 'true' : undefined"
        class="block w-full rounded-lg border bg-white px-3 py-2 text-sm transition-colors focus:border-violet-600 focus:ring-4 focus:ring-violet-100 focus:outline-none"
        :class="[
          erro ? 'border-red-500' : 'border-slate-300 hover:border-violet-300',
          prefixo ? 'h-11 pl-10 text-lg' : '',
        ]"
        @input="aoDigitar"
      />
    </div>
    <p v-if="erro" class="mt-1 text-sm text-red-600">{{ erro }}</p>
    <p v-else-if="dica" class="mt-1 text-sm text-slate-500">{{ dica }}</p>
  </div>
</template>
