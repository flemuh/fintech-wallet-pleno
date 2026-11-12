<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { LocationQuery, LocationQueryRaw, LocationQueryValue } from 'vue-router'

import { listarTransacoes } from '@/api/wallet'
import type { FiltrosDoHistorico, PaginaDeTransacoes } from '@/api/wallet'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppDateField from '@/components/ui/AppDateField.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import TransactionTable from '@/components/wallet/TransactionTable.vue'
import { useEnvio } from '@/composables/useEnvio'
import { dataBrParaIso, dataIsoParaBr } from '@/utils/date'

const TRANSACOES_POR_PAGINA = 10

const rota = useRoute()
const roteador = useRouter()
const { enviando: carregando, erroGeral, errosDosCampos, enviar } = useEnvio()

const pagina = ref<PaginaDeTransacoes | null>(null)
// As datas ficam em dd/mm/aaaa na tela; a URL e a API usam aaaa-mm-dd
const formulario = reactive({ type: '', from: '', to: '' })
const errosDasDatas = reactive<{ from: string | null; to: string | null }>({ from: null, to: null })

// Com filtro ativo, lista vazia quer dizer "nada encontrado", e não conta sem movimentação
const temFiltro = computed(() => Boolean(rota.query.type || rota.query.from || rota.query.to))

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
    formulario.from = dataIsoParaBr(filtros.from ?? '')
    formulario.to = dataIsoParaBr(filtros.to ?? '')
    errosDasDatas.from = null
    errosDasDatas.to = null

    await enviar(async () => {
      pagina.value = await listarTransacoes({ ...filtros, per_page: TRANSACOES_POR_PAGINA })
    })
  },
  { immediate: true },
)

// Campo vazio é aceito (sem filtro); preenchido, precisa ser uma data que existe
function lerData(campo: 'from' | 'to'): string | null | undefined {
  if (!formulario[campo]) return undefined

  const dataIso = dataBrParaIso(formulario[campo])
  errosDasDatas[campo] = dataIso ? null : 'Informe uma data válida, como 12/11/2026.'

  return dataIso
}

function aplicarFiltros(): void {
  const inicio = lerData('from')
  const fim = lerData('to')
  if (inicio === null || fim === null) return

  const query: LocationQueryRaw = {}

  if (formulario.type) query.type = formulario.type
  if (inicio) query.from = inicio
  if (fim) query.to = fim

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
      class="cartao mt-6 grid gap-4 p-4 sm:grid-cols-[repeat(3,minmax(0,1fr))_auto] sm:items-end"
      novalidate
      @submit.prevent="aplicarFiltros"
    >
      <div>
        <label for="filtro-tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
        <select id="filtro-tipo" v-model="formulario.type" class="campo mt-1">
          <option value="">Todos</option>
          <option value="credit">Depósitos (crédito)</option>
          <option value="debit">Saques (débito)</option>
        </select>
        <p v-if="errosDosCampos.type" class="mt-1 text-sm text-red-600">
          {{ errosDosCampos.type[0] }}
        </p>
      </div>

      <AppDateField
        id="filtro-inicio"
        v-model="formulario.from"
        label="Data inicial"
        :erro="errosDasDatas.from ?? errosDosCampos.from?.[0]"
      />
      <AppDateField
        id="filtro-fim"
        v-model="formulario.to"
        label="Data final"
        :erro="errosDasDatas.to ?? errosDosCampos.to?.[0]"
      />

      <div class="flex gap-2">
        <button type="submit" :disabled="carregando" class="botao-primario py-2">Filtrar</button>
        <button type="button" class="botao-secundario py-2" @click="limparFiltros">Limpar</button>
      </div>
    </form>

    <AppAlert v-if="erroGeral" tipo="erro" class="mt-4">{{ erroGeral }}</AppAlert>
    <p v-if="carregando && !pagina" class="mt-4 text-slate-500">Carregando...</p>

    <template v-if="pagina">
      <TransactionTable
        :transacoes="pagina.data"
        :mensagem-vazia="temFiltro ? 'Nenhuma transação encontrada para esses filtros.' : undefined"
        class="mt-4"
      />
      <AppPagination
        class="mt-4"
        :pagina-atual="pagina.meta.current_page"
        :ultima-pagina="pagina.meta.last_page"
        @mudar="mudarPagina"
      />
    </template>
  </section>
</template>
