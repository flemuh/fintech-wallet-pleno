<script setup lang="ts">
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
      class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
      @click="$emit('mudar', paginaAtual - 1)"
    >
      Anterior
    </button>
    <span class="text-slate-600">Página {{ paginaAtual }} de {{ ultimaPagina }}</span>
    <button
      type="button"
      :disabled="paginaAtual >= ultimaPagina"
      class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-100 disabled:opacity-50"
      @click="$emit('mudar', paginaAtual + 1)"
    >
      Próxima
    </button>
  </nav>
</template>
