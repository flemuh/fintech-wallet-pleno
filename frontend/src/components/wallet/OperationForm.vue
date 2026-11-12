<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Component } from 'vue'

import AppField from '@/components/ui/AppField.vue'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { formatarCentavos, lerValorEmReais, mascararReais } from '@/utils/money'

const props = defineProps<{
  prefixoDoId: string
  titulo: string
  rotuloDoBotao: string
  mensagemDeSucesso: string
  // A chave de idempotência vai junto: a API nunca executa duas vezes a mesma tentativa
  executar: (valorEmReais: number, chave: string) => Promise<unknown>
  // Ação principal usa o botão cheio; a secundária, o botão com contorno
  destaque?: 'primario' | 'secundario'
  // Componente de ícone exibido ao lado do título (opcional)
  icone?: Component
  // Texto de ajuda embaixo do campo
  dica?: string
  // Em centavos. Quando informado, valores acima dele são bloqueados antes de chamar a API
  saldoDisponivel?: number
}>()

const valor = ref('')
const erroLocal = ref<string | null>(null)
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

// Uma chave por operação: se a resposta não chegar (rede caiu) e a pessoa reenviar o mesmo valor,
// a chave se repete e a API devolve a operação já feita. Valor novo ou operação concluída: chave nova
let tentativa: { centavos: number; chave: string } | null = null

function chaveDaOperacao(centavos: number): string {
  if (tentativa?.centavos !== centavos) {
    tentativa = { centavos, chave: crypto.randomUUID() }
  }

  return tentativa.chave
}

const classeDoBotao = computed(() =>
  props.destaque === 'secundario' ? 'botao-secundario' : 'botao-primario',
)

const excedeOSaldo = computed(() => {
  const centavos = lerValorEmReais(valor.value)

  return (
    props.saldoDisponivel !== undefined && centavos !== null && centavos > props.saldoDisponivel
  )
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

  const chave = chaveDaOperacao(centavos)
  const deuCerto = await enviar(() => props.executar(centavos / 100, chave))
  if (deuCerto) {
    tentativa = null
    valor.value = ''
    toast.sucesso(props.mensagemDeSucesso)
  } else if (erroGeral.value) {
    toast.erro(erroGeral.value)
  }
}
</script>

<template>
  <form class="cartao space-y-4 p-5" novalidate @submit.prevent="enviarFormulario">
    <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
      <component :is="icone" v-if="icone" class="size-5 text-violet-700" aria-hidden="true" />
      {{ titulo }}
    </h2>

    <AppField
      :id="`${prefixoDoId}-valor`"
      v-model="valor"
      label="Valor (R$)"
      prefixo="R$"
      placeholder="0,00"
      inputmode="numeric"
      autocomplete="off"
      :dica="dica ?? 'Exemplo: 10,50'"
      :mascara="mascararReais"
      :erro="erroDoValor"
    />

    <button
      type="submit"
      :disabled="enviando || excedeOSaldo"
      class="w-full"
      :class="classeDoBotao"
    >
      {{ enviando ? 'Enviando...' : rotuloDoBotao }}
    </button>
  </form>
</template>
