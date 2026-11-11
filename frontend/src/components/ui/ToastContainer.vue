<script setup lang="ts">
import { CircleCheck, CircleX, Info, TriangleAlert, X } from 'lucide-vue-next'
import type { Component } from 'vue'

import { useToast } from '@/composables/useToast'
import type { TipoDeToast } from '@/composables/useToast'

const { toasts, remover } = useToast()

const ESTILOS: Record<TipoDeToast, string> = {
  sucesso: 'bg-green-600',
  erro: 'bg-red-600',
  info: 'bg-sky-600',
  alerta: 'bg-amber-500',
}

const ICONES: Record<TipoDeToast, Component> = {
  sucesso: CircleCheck,
  erro: CircleX,
  info: Info,
  alerta: TriangleAlert,
}
</script>

<template>
  <div
    class="pointer-events-none fixed top-4 right-4 z-50 flex w-72 max-w-[calc(100vw-2rem)] flex-col gap-2"
    aria-live="polite"
  >
    <div
      v-for="toast in toasts"
      :key="toast.id"
      :role="toast.tipo === 'erro' ? 'alert' : 'status'"
      class="pointer-events-auto flex items-start gap-2 rounded-md px-3 py-2 text-sm text-white shadow-lg"
      :class="ESTILOS[toast.tipo]"
    >
      <component :is="ICONES[toast.tipo]" class="size-5 shrink-0" aria-hidden="true" />
      <span class="flex-1">{{ toast.mensagem }}</span>
      <button
        type="button"
        aria-label="Fechar"
        class="rounded p-0.5 hover:bg-black/10"
        @click="remover(toast.id)"
      >
        <X class="size-4" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>
