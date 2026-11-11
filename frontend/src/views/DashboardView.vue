<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'

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
          <dt class="text-sm text-slate-500">Saldo atual</dt>
          <dd class="mt-1 text-2xl font-semibold text-slate-900">
            {{ formatarCentavos(resumo.balance) }}
          </dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <dt class="text-sm text-slate-500">Entradas no mês</dt>
          <dd class="mt-1 text-2xl font-semibold text-emerald-600">
            {{ formatarCentavos(resumo.month_credit) }}
          </dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
          <dt class="text-sm text-slate-500">Saídas no mês</dt>
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
          :executar="wallet.depositar"
        />
      </div>

      <h2 class="mt-8 text-lg font-semibold text-slate-900">Últimas transações</h2>
      <TransactionTable :transacoes="recentes" class="mt-3" />
    </template>
  </section>
</template>
