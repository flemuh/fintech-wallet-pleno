<script setup lang="ts">
import { ArrowDownLeft, ArrowRight, ArrowUpRight, Minus, Plus, Wallet } from '@lucide/vue'
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
      <dl class="mt-6 grid gap-4 md:grid-cols-[1.3fr_1fr_1fr]">
        <div class="rounded-2xl bg-violet-800 p-5 text-white shadow-sm">
          <dt class="flex items-center gap-2 text-sm text-violet-200">
            <Wallet class="size-4" aria-hidden="true" />
            Saldo atual
          </dt>
          <dd class="mt-2 text-3xl font-semibold tracking-tight">
            {{ formatarCentavos(resumo.balance) }}
          </dd>
        </div>
        <div class="cartao p-5">
          <dt class="flex items-center gap-2 text-sm text-slate-500">
            <span class="flex size-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
              <ArrowDownLeft class="size-4" aria-hidden="true" />
            </span>
            Depositado no mês
          </dt>
          <dd class="mt-3 text-xl font-semibold text-emerald-700">
            {{ formatarCentavos(resumo.month_credit) }}
          </dd>
        </div>
        <div class="cartao p-5">
          <dt class="flex items-center gap-2 text-sm text-slate-500">
            <span class="flex size-8 items-center justify-center rounded-full bg-red-50 text-red-700">
              <ArrowUpRight class="size-4" aria-hidden="true" />
            </span>
            Sacado no mês
          </dt>
          <dd class="mt-3 text-xl font-semibold text-red-700">
            {{ formatarCentavos(resumo.month_debit) }}
          </dd>
        </div>
      </dl>

      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <OperationForm
          prefixo-do-id="deposito"
          titulo="Depositar"
          rotulo-do-botao="Depositar"
          mensagem-de-sucesso="Depósito realizado com sucesso."
          destaque="primario"
          dica="Máximo por operação: R$ 999.999,99"
          :icone="Plus"
          :executar="wallet.depositar"
        />
        <OperationForm
          prefixo-do-id="saque"
          titulo="Sacar"
          rotulo-do-botao="Sacar"
          mensagem-de-sucesso="Saque realizado com sucesso."
          destaque="secundario"
          :dica="`Disponível: ${formatarCentavos(resumo.balance)}`"
          :icone="Minus"
          :saldo-disponivel="resumo.balance"
          :executar="wallet.sacar"
        />
      </div>

      <div class="mt-8 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Últimas transações</h2>
        <RouterLink
          :to="{ name: 'transactions' }"
          class="flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-medium text-violet-700 transition-colors hover:bg-violet-50 hover:text-violet-900"
        >
          Ver extrato completo
          <ArrowRight class="size-4" aria-hidden="true" />
        </RouterLink>
      </div>
      <TransactionTable :transacoes="recentes" class="mt-3" />
    </template>
  </section>
</template>
