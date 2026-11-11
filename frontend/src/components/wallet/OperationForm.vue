<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Component } from 'vue'

import AppField from '@/components/ui/AppField.vue'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { formatarCentavos, lerValorEmReais } from '@/utils/money'

const props = defineProps<{
  prefixoDoId: string
  titulo: string
  rotuloDoBotao: string
  mensagemDeSucesso: string
  executar: (valorEmReais: number) => Promise<unknown>
  // Componente de ícone exibido no botão (opcional)
  icone?: Component
  // Em centavos. Quando informado, valores acima dele são bloqueados antes de chamar a API
  saldoDisponivel?: number
}>()

const valor = ref('')
const erroLocal = ref<string | null>(null)
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const excedeOSaldo = computed(() => {
  const centavos = lerValorEmReais(valor.value)

  return props.saldoDisponivel !== undefined && centavos !== null && centavos > props.saldoDisponivel
})

const erroDoValor = computed(() => {
  if (erroLocal.value) return erroLocal.value

  if (excedeOSaldo.value) {
    return `Saldo insuficiente. Seu saldo é ${formatarCentavos(props.saldoDisponivel ?? 0)}.`
  }

  return errosDosCampos.value.amount?.[0]
})

async function enviarFormulario(): Promise<void> {
  erroLocal.value = null

  const centavos = lerValorEmReais(valor.value)
  if (centavos === null || centavos === 0) {
    erroLocal.value = 'Informe um valor maior que zero, como 10,50.'
    return
  }

  if (excedeOSaldo.value) return

  const deuCerto = await enviar(() => props.executar(centavos / 100))
  if (deuCerto) {
    valor.value = ''
    toast.sucesso(props.mensagemDeSucesso)
  } else if (erroGeral.value) {
    toast.erro(erroGeral.value)
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
      :disabled="enviando || excedeOSaldo"
      class="flex w-full items-center justify-center gap-2 rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
    >
      <component :is="icone" v-if="icone && !enviando" class="size-4" aria-hidden="true" />
      {{ enviando ? 'Enviando...' : rotuloDoBotao }}
    </button>
  </form>
</template>
