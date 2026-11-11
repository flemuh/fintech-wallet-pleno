<script setup lang="ts">
import type { Transacao } from '@/api/wallet'
import { formatarDataEHora } from '@/utils/date'
import { formatarCentavos } from '@/utils/money'

defineProps<{
  transacoes: Transacao[]
}>()

function rotuloDoTipo(transacao: Transacao): string {
  return transacao.type === 'credit' ? 'Depósito (crédito)' : 'Saque (débito)'
}

function valorComSinal(transacao: Transacao): string {
  const sinal = transacao.type === 'credit' ? '+' : '-'
  return `${sinal} ${formatarCentavos(transacao.amount)}`
}
</script>

<template>
  <div>
    <p v-if="transacoes.length === 0" class="cartao p-6 text-center text-sm text-slate-500">
      Nenhuma transação ainda.
    </p>
    <div v-else class="cartao overflow-x-auto">
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
                :class="
                  transacao.type === 'credit'
                    ? 'bg-emerald-50 text-emerald-800'
                    : 'bg-red-50 text-red-800'
                "
              >
                {{ rotuloDoTipo(transacao) }}
              </span>
            </td>
            <td
              class="px-4 py-3 text-right font-medium"
              :class="transacao.type === 'credit' ? 'text-emerald-700' : 'text-red-700'"
            >
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
