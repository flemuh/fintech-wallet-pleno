<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

defineProps<{
  paginaAtual: number
  ultimaPagina: number
}>()

defineEmits<{
  mudar: [pagina: number]
}>()
</script>

<template>
  <nav
    v-if="ultimaPagina > 1"
    class="flex items-center justify-between text-sm"
    aria-label="Paginação"
  >
    <button
      type="button"
      :disabled="paginaAtual <= 1"
      class="botao-secundario px-3 py-1.5"
      @click="$emit('mudar', paginaAtual - 1)"
    >
      <ChevronLeft class="size-4" aria-hidden="true" />
      Anterior
    </button>
    <span class="text-slate-600">Página {{ paginaAtual }} de {{ ultimaPagina }}</span>
    <button
      type="button"
      :disabled="paginaAtual >= ultimaPagina"
      class="botao-secundario px-3 py-1.5"
      @click="$emit('mudar', paginaAtual + 1)"
    >
      Próxima
      <ChevronRight class="size-4" aria-hidden="true" />
    </button>
  </nav>
</template>
