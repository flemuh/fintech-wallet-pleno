<script setup lang="ts">
import type { Transacao } from '@/api/wallet'
import { formatarDataEHora } from '@/utils/date'
import { formatarCentavos } from '@/utils/money'

defineProps<{
  transacoes: Transacao[]
  // Texto mostrado quando a lista está vazia
  mensagemVazia?: string
}>()

function rotuloDoTipo(transacao: Transacao): string {
  return transacao.type === 'credit' ? 'Depósito (crédito)' : 'Saque (débito)'
}

function classeDoSelo(transacao: Transacao): string {
  return transacao.type === 'credit' ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'
}

function classeDoValor(transacao: Transacao): string {
  return transacao.type === 'credit' ? 'text-emerald-700' : 'text-red-700'
}

function valorComSinal(transacao: Transacao): string {
  const sinal = transacao.type === 'credit' ? '+' : '-'
  return `${sinal} ${formatarCentavos(transacao.amount)}`
}
</script>

<template>
  <div>
    <p v-if="transacoes.length === 0" class="cartao p-6 text-center text-sm text-slate-500">
      {{ mensagemVazia ?? 'Nenhuma transação ainda.' }}
    </p>
    <!-- Celular: cada transação vira um cartão, sem rolagem lateral -->
    <ul v-else class="cartao divide-y divide-violet-100 sm:hidden">
      <li
        v-for="transacao in transacoes"
        :key="`cartao-${transacao.id}`"
        class="space-y-1 px-4 py-3"
      >
        <div class="flex items-center justify-between gap-2">
          <span
            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="classeDoSelo(transacao)"
          >
            {{ rotuloDoTipo(transacao) }}
          </span>
          <span class="font-semibold whitespace-nowrap" :class="classeDoValor(transacao)">
            {{ valorComSinal(transacao) }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
          <span>{{ formatarDataEHora(transacao.created_at) }}</span>
          <span class="whitespace-nowrap"
            >Saldo: {{ formatarCentavos(transacao.balance_after) }}</span
          >
        </div>
      </li>
    </ul>
    <!-- Telas maiores: tabela completa -->
    <div v-if="transacoes.length > 0" class="cartao hidden overflow-x-auto sm:block">
      <table class="min-w-full text-sm">
        <thead class="bg-violet-50 text-left text-violet-900">
          <tr>
            <th class="px-4 py-3 font-medium">Data</th>
            <th class="px-4 py-3 font-medium">Tipo</th>
            <th class="px-4 py-3 text-right font-medium">Valor</th>
            <th class="px-4 py-3 text-right font-medium">Saldo após</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-violet-100">
          <tr
            v-for="transacao in transacoes"
            :key="transacao.id"
            class="transition-colors odd:bg-white even:bg-violet-50/40 hover:bg-violet-100/70"
          >
            <td class="px-4 py-3 text-slate-600">{{ formatarDataEHora(transacao.created_at) }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="classeDoSelo(transacao)"
              >
                {{ rotuloDoTipo(transacao) }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium" :class="classeDoValor(transacao)">
              {{ valorComSinal(transacao) }}
            </td>
            <td class="px-4 py-3 text-right text-slate-600">
              {{ formatarCentavos(transacao.balance_after) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
