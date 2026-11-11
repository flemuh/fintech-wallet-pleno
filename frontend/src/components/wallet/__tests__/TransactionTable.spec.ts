import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import type { Transacao } from '@/api/wallet'
import TransactionTable from '@/components/wallet/TransactionTable.vue'

const deposito: Transacao = {
  id: 1,
  type: 'credit',
  amount: 1050,
  balance_after: 1050,
  created_at: '2026-06-15T15:30:00+00:00',
}

const saque: Transacao = {
  id: 2,
  type: 'debit',
  amount: 500,
  balance_after: 550,
  created_at: '2026-06-15T16:00:00+00:00',
}

describe('TransactionTable', () => {
  it('mostra uma mensagem quando não há transações', () => {
    const wrapper = mount(TransactionTable, { props: { transacoes: [] } })

    expect(wrapper.text()).toContain('Nenhuma transação ainda.')
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('mostra o depósito com sinal positivo e o saldo após a operação', () => {
    const wrapper = mount(TransactionTable, { props: { transacoes: [deposito] } })

    expect(wrapper.text()).toContain('Depósito')
    expect(wrapper.text()).toContain('+ R$\u00a010,50')
    expect(wrapper.text()).toContain('R$\u00a010,50')
  })

  it('mostra o saque com sinal negativo', () => {
    const wrapper = mount(TransactionTable, { props: { transacoes: [saque] } })

    expect(wrapper.text()).toContain('Saque')
    expect(wrapper.text()).toContain('- R$\u00a05,00')
    expect(wrapper.text()).toContain('R$\u00a05,50')
  })
})
