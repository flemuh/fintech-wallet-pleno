<script setup lang="ts">
import { CalendarDays } from '@lucide/vue'
import { computed, ref } from 'vue'

import AppField from '@/components/ui/AppField.vue'
import { dataBrParaIso, dataIsoParaBr, mascararData } from '@/utils/date'

defineProps<{
  id: string
  label: string
  erro?: string
}>()

// Texto em dd/mm/aaaa, exatamente o que aparece no campo
const valor = defineModel<string>({ required: true })

// Calendário nativo escondido: o navegador mostra o seletor e o campo continua em dd/mm/aaaa
const calendario = ref<HTMLInputElement | null>(null)
const valorDoCalendario = computed(() => dataBrParaIso(valor.value) ?? '')

function abrirCalendario(): void {
  try {
    calendario.value?.showPicker()
  } catch {
    // Navegador sem showPicker: a digitação no campo continua funcionando
  }
}

function aoEscolherNoCalendario(evento: Event): void {
  valor.value = dataIsoParaBr((evento.target as HTMLInputElement).value)
}
</script>

<template>
  <AppField
    :id="id"
    v-model="valor"
    :label="label"
    placeholder="dd/mm/aaaa"
    inputmode="numeric"
    autocomplete="off"
    :mascara="mascararData"
    :erro="erro"
  >
    <template #sufixo>
      <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-slate-500 transition-colors hover:text-violet-700 focus-visible:text-violet-700 focus-visible:outline-none"
        :aria-label="`Abrir calendário: ${label}`"
        @click="abrirCalendario"
      >
        <CalendarDays class="size-4" aria-hidden="true" />
      </button>
      <input
        :id="`${id}-calendario`"
        ref="calendario"
        type="date"
        :value="valorDoCalendario"
        tabindex="-1"
        aria-hidden="true"
        class="pointer-events-none absolute right-0 bottom-0 size-px opacity-0"
        @change="aoEscolherNoCalendario"
      />
    </template>
  </AppField>
</template>
