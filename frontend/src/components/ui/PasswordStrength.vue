<script setup lang="ts">
import { Check, X } from '@lucide/vue'
import { computed } from 'vue'

import { avaliarSenha, forcaDaSenha } from '@/utils/validacao'

const props = defineProps<{ senha: string }>()

const ESTILOS = {
  fraca: { barra: 'bg-red-500', texto: 'text-red-700', rotulo: 'fraca', preenchidas: 1 },
  media: { barra: 'bg-amber-500', texto: 'text-amber-700', rotulo: 'média', preenchidas: 2 },
  forte: { barra: 'bg-emerald-600', texto: 'text-emerald-700', rotulo: 'forte', preenchidas: 3 },
} as const

const criterios = computed(() => avaliarSenha(props.senha))
const estilo = computed(() => ESTILOS[forcaDaSenha(props.senha)])
</script>

<template>
  <div class="space-y-2">
    <div v-if="senha" class="flex items-center gap-2">
      <div class="flex flex-1 gap-1" aria-hidden="true">
        <span
          v-for="parte in 3"
          :key="parte"
          class="h-1.5 flex-1 rounded-full transition-colors"
          :class="parte <= estilo.preenchidas ? estilo.barra : 'bg-slate-200'"
        />
      </div>
      <span class="text-xs font-medium" :class="estilo.texto" aria-live="polite">
        Senha {{ estilo.rotulo }}
      </span>
    </div>
    <ul class="space-y-0.5 text-xs">
      <li
        v-for="criterio in criterios"
        :key="criterio.rotulo"
        class="flex items-center gap-1.5"
        :class="criterio.atendido ? 'text-emerald-700' : 'text-slate-500'"
      >
        <Check v-if="criterio.atendido" class="size-3.5" aria-hidden="true" />
        <X v-else class="size-3.5" aria-hidden="true" />
        {{ criterio.rotulo }}
      </li>
    </ul>
  </div>
</template>
