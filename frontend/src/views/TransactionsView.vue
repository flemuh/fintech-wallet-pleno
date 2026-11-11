<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { LocationQuery, LocationQueryRaw, LocationQueryValue } from 'vue-router'

import { listarTransacoes } from '@/api/wallet'
import type { FiltrosDoHistorico, PaginaDeTransacoes } from '@/api/wallet'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import TransactionTable from '@/components/wallet/TransactionTable.vue'
import { useEnvio } from '@/composables/useEnvio'

const TRANSACOES_POR_PAGINA = 10

const rota = useRoute()
const roteador = useRouter()
const { enviando: carregando, erroGeral, errosDosCampos, enviar } = useEnvio()

const pagina = ref<PaginaDeTransacoes | null>(null)
const formulario = reactive({ type: '', from: '', to: '' })

function primeiroTexto(valor: LocationQueryValue | LocationQueryValue[] | undefined): string {
  const texto = Array.isArray(valor) ? valor[0] : valor

  return texto ?? ''
}

// A URL é a fonte da verdade: filtros e página sobrevivem ao recarregar a tela
function lerFiltrosDaRota(query: LocationQuery): FiltrosDoHistorico {
  const filtros: FiltrosDoHistorico = {}
  const tipo = primeiroTexto(query.type)
  const inicio = primeiroTexto(query.from)
  const fim = primeiroTexto(query.to)
  const numeroDaPagina = Number(primeiroTexto(query.page))

  if (tipo === 'credit' || tipo === 'debit') filtros.type = tipo
  if (inicio) filtros.from = inicio
  if (fim) filtros.to = fim
  if (Number.isInteger(numeroDaPagina) && numeroDaPagina > 0) filtros.page = numeroDaPagina

  return filtros
}

watch(
  () => rota.query,
  async (query) => {
    // Ao sair da tela a rota muda antes da tela ser desmontada: não busca de novo
    if (rota.name !== 'transactions') return

    const filtros = lerFiltrosDaRota(query)
    formulario.type = filtros.type ?? ''
    formulario.from = filtros.from ?? ''
    formulario.to = filtros.to ?? ''

    await enviar(async () => {
      pagina.value = await listarTransacoes({ ...filtros, per_page: TRANSACOES_POR_PAGINA })
    })
  },
  { immediate: true },
)

function aplicarFiltros(): void {
  const query: LocationQueryRaw = {}

  if (formulario.type) query.type = formulario.type
  if (formulario.from) query.from = formulario.from
  if (formulario.to) query.to = formulario.to

  void roteador.push({ query })
}

function limparFiltros(): void {
  void roteador.push({ query: {} })
}

function mudarPagina(numero: number): void {
  void roteador.push({ query: { ...rota.query, page: String(numero) } })
}
</script>

<template>
  <section>
    <h1 class="text-2xl font-semibold text-slate-900">Extrato</h1>

    <form
      class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-4 sm:items-end"
      novalidate
      @submit.prevent="aplicarFiltros"
    >
      <div>
        <label for="filtro-tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
        <select
          id="filtro-tipo"
          v-model="formulario.type"
          class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        >
          <option value="">Todos</option>
          <option value="credit">Depósitos</option>
          <option value="debit">Saques</option>
        </select>
        <p v-if="errosDosCampos.type" class="mt-1 text-sm text-red-600">
          {{ errosDosCampos.type[0] }}
        </p>
      </div>

      <AppField
        id="filtro-inicio"
        v-model="formulario.from"
        label="Data inicial"
        type="date"
        :erro="errosDosCampos.from?.[0]"
      />
      <AppField
        id="filtro-fim"
        v-model="formulario.to"
        label="Data final"
        type="date"
        :erro="errosDosCampos.to?.[0]"
      />

      <div class="flex gap-2">
        <button
          type="submit"
          :disabled="carregando"
          class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          Filtrar
        </button>
        <button
          type="button"
          class="rounded border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
          @click="limparFiltros"
        >
          Limpar
        </button>
      </div>
    </form>

    <AppAlert v-if="erroGeral" tipo="erro" class="mt-4">{{ erroGeral }}</AppAlert>
    <p v-if="carregando && !pagina" class="mt-4 text-slate-500">Carregando...</p>

    <template v-if="pagina">
      <TransactionTable :transacoes="pagina.data" class="mt-4" />
      <AppPagination
        class="mt-4"
        :pagina-atual="pagina.meta.current_page"
        :ultima-pagina="pagina.meta.last_page"
        @mudar="mudarPagina"
      />
    </template>
  </section>
</template>
