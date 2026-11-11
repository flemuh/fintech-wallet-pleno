<script setup lang="ts">
import { computed, ref } from 'vue'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import { useEnvio } from '@/composables/useEnvio'
import { lerValorEmReais } from '@/utils/money'

const props = defineProps<{
  prefixoDoId: string
  titulo: string
  rotuloDoBotao: string
  mensagemDeSucesso: string
  executar: (valorEmReais: number) => Promise<unknown>
}>()

const valor = ref('')
const sucesso = ref(false)
const erroLocal = ref<string | null>(null)
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const erroDoValor = computed(() => erroLocal.value ?? errosDosCampos.value.amount?.[0])

async function enviarFormulario(): Promise<void> {
  sucesso.value = false
  erroLocal.value = null

  const centavos = lerValorEmReais(valor.value)
  if (centavos === null || centavos === 0) {
    erroLocal.value = 'Informe um valor maior que zero, como 10,50.'
    return
  }

  const deuCerto = await enviar(() => props.executar(centavos / 100))
  if (deuCerto) {
    valor.value = ''
    sucesso.value = true
  }
}
</script>

<template>
  <form
    class="space-y-4 rounded-lg border border-slate-200 bg-white p-4"
    novalidate
    @submit.prevent="enviarFormulario"
  >
    <h2 class="text-lg font-semibold text-slate-900">{{ titulo }}</h2>

    <AppAlert v-if="sucesso" tipo="sucesso">{{ mensagemDeSucesso }}</AppAlert>
    <AppAlert v-if="erroGeral" tipo="erro">{{ erroGeral }}</AppAlert>

    <AppField
      :id="`${prefixoDoId}-valor`"
      v-model="valor"
      label="Valor (R$)"
      autocomplete="off"
      dica="Exemplo: 10,50"
      :erro="erroDoValor"
    />

    <button
      type="submit"
      :disabled="enviando"
      class="w-full rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
    >
      {{ enviando ? 'Enviando...' : rotuloDoBotao }}
    </button>
  </form>
</template>
