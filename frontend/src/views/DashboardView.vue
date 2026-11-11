<script setup lang="ts">
import { ArrowDownToLine, ArrowRight, ArrowUpFromLine, Wallet } from 'lucide-vue-next'
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import OperationForm from '@/components/wallet/OperationForm.vue'
import TransactionTable from '@/components/wallet/TransactionTable.vue'
import { useWalletStore } from '@/stores/wallet'
import { formatarCentavos } from '@/utils/money'

const wallet = useWalletStore()
const { resumo, recentes, carregando, erro } = storeToRefs(wallet)

onMounted(() => wallet.carregarPainel())
</script>

<template>
  <section>
    <h1 class="text-2xl font-semibold text-slate-900">Minha carteira</h1>

    <AppAlert v-if="erro" tipo="erro" class="mt-4">{{ erro }}</AppAlert>
    <p v-else-if="carregando && !resumo" class="mt-4 text-slate-500">Carregando...</p>

    <template v-if="resumo">
      <dl class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <dt class="flex items-center gap-2 text-sm text-slate-500">
            <Wallet class="size-4 text-slate-400" aria-hidden="true" />
            Saldo atual
          </dt>
          <dd class="mt-1 text-2xl font-semibold text-slate-900">
            {{ formatarCentavos(resumo.balance) }}
          </dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <dt class="flex items-center gap-2 text-sm text-slate-500">
            <ArrowDownToLine class="size-4 text-emerald-500" aria-hidden="true" />
            Entradas no mês
          </dt>
          <dd class="mt-1 text-2xl font-semibold text-emerald-600">
            {{ formatarCentavos(resumo.month_credit) }}
          </dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <dt class="flex items-center gap-2 text-sm text-slate-500">
            <ArrowUpFromLine class="size-4 text-red-500" aria-hidden="true" />
            Saídas no mês
          </dt>
          <dd class="mt-1 text-2xl font-semibold text-red-600">
            {{ formatarCentavos(resumo.month_debit) }}
          </dd>
        </div>
      </dl>

      <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <OperationForm
          prefixo-do-id="deposito"
          titulo="Depositar"
          rotulo-do-botao="Depositar"
          mensagem-de-sucesso="Depósito realizado com sucesso."
          :icone="ArrowDownToLine"
          :executar="wallet.depositar"
        />
        <OperationForm
          prefixo-do-id="saque"
          titulo="Sacar"
          rotulo-do-botao="Sacar"
          mensagem-de-sucesso="Saque realizado com sucesso."
          :icone="ArrowUpFromLine"
          :saldo-disponivel="resumo.balance"
          :executar="wallet.sacar"
        />
      </div>

      <div class="mt-8 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Últimas transações</h2>
        <RouterLink
          :to="{ name: 'transactions' }"
          class="flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800"
        >
          Ver extrato completo
          <ArrowRight class="size-4" aria-hidden="true" />
        </RouterLink>
      </div>
      <TransactionTable :transacoes="recentes" class="mt-3" />
    </template>
  </section>
</template>
