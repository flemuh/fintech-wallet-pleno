<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import type { TipoDeToast } from '@/composables/useToast'

const { toasts, remover } = useToast()

const ESTILOS: Record<TipoDeToast, string> = {
  sucesso: 'bg-green-600',
  erro: 'bg-red-600',
  info: 'bg-sky-600',
  alerta: 'bg-amber-500',
}
</script>

<template>
  <div
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4"
    aria-live="polite"
  >
    <div
      v-for="toast in toasts"
      :key="toast.id"
      :role="toast.tipo === 'erro' ? 'alert' : 'status'"
      class="pointer-events-auto flex w-full max-w-sm items-start justify-between gap-3 rounded px-4 py-3 text-sm text-white shadow-lg"
      :class="ESTILOS[toast.tipo]"
    >
      <span>{{ toast.mensagem }}</span>
      <button
        type="button"
        aria-label="Fechar"
        class="leading-none font-bold"
        @click="remover(toast.id)"
      >
        ×
      </button>
    </div>
  </div>
</template>
