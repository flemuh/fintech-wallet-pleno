<script setup lang="ts">
import type { Transacao } from '@/api/wallet'
import { formatarDataEHora } from '@/utils/date'
import { formatarCentavos } from '@/utils/money'

defineProps<{
  transacoes: Transacao[]
}>()

function rotuloDoTipo(transacao: Transacao): string {
  return transacao.type === 'credit' ? 'Depósito' : 'Saque'
}

function valorComSinal(transacao: Transacao): string {
  const sinal = transacao.type === 'credit' ? '+' : '-'
  return `${sinal} ${formatarCentavos(transacao.amount)}`
}
</script>

<template>
  <div>
    <p v-if="transacoes.length === 0" class="text-sm text-slate-500">Nenhuma transação ainda.</p>
    <div v-else class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
          <tr>
            <th class="px-4 py-2 font-medium">Data</th>
            <th class="px-4 py-2 font-medium">Tipo</th>
            <th class="px-4 py-2 text-right font-medium">Valor</th>
            <th class="px-4 py-2 text-right font-medium">Saldo após</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="transacao in transacoes" :key="transacao.id">
            <td class="px-4 py-2 text-slate-600">{{ formatarDataEHora(transacao.created_at) }}</td>
            <td class="px-4 py-2 text-slate-900">{{ rotuloDoTipo(transacao) }}</td>
            <td
              class="px-4 py-2 text-right font-medium"
              :class="transacao.type === 'credit' ? 'text-emerald-600' : 'text-red-600'"
            >
              {{ valorComSinal(transacao) }}
            </td>
            <td class="px-4 py-2 text-right text-slate-600">
              {{ formatarCentavos(transacao.balance_after) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
