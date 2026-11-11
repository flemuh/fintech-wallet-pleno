import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import * as api from '@/api/wallet'
import DashboardView from '@/views/DashboardView.vue'

vi.mock('@/api/wallet')

// Roteador mínimo: o painel só precisa resolver o link para o extrato
const paginaVazia = { render: () => null }

async function montarPainel() {
  const roteador = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: paginaVazia },
      { path: '/transactions', name: 'transactions', component: paginaVazia },
    ],
  })
  await roteador.push('/')

  return mount(DashboardView, { global: { plugins: [createPinia(), roteador] } })
}

describe('DashboardView', () => {
  beforeEach(() => {
    vi.resetAllMocks()
  })

  it('mostra saldo, totais do mês e as últimas transações', async () => {
    vi.mocked(api.buscarResumo).mockResolvedValue({
      balance: 49610,
      month_credit: 28575,
      month_debit: 15325,
    })
    vi.mocked(api.listarTransacoes).mockResolvedValue({
      data: [
        {
          id: 1,
          type: 'credit',
          amount: 1050,
          balance_after: 49610,
          created_at: '2026-06-15T15:30:00+00:00',
        },
      ],
      meta: { current_page: 1, last_page: 1, per_page: 5, total: 1 },
    })

    const wrapper = await montarPainel()
    await flushPromises()

    expect(wrapper.text()).toContain('R$\u00a0496,10')
    expect(wrapper.text()).toContain('R$\u00a0285,75')
    expect(wrapper.text()).toContain('R$\u00a0153,25')
    expect(wrapper.text()).toContain('Depósito')
  })

  it('leva ao extrato completo pelo link abaixo das últimas transações', async () => {
    vi.mocked(api.buscarResumo).mockResolvedValue({
      balance: 0,
      month_credit: 0,
      month_debit: 0,
    })
    vi.mocked(api.listarTransacoes).mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 5, total: 0 },
    })

    const wrapper = await montarPainel()
    await flushPromises()

    const link = wrapper.find('a[href="/transactions"]')

    expect(link.exists()).toBe(true)
    expect(link.text()).toContain('Ver extrato completo')
  })

  it('mostra um alerta quando a API falha', async () => {
    vi.mocked(api.buscarResumo).mockRejectedValue(new Error('falhou'))
    vi.mocked(api.listarTransacoes).mockRejectedValue(new Error('falhou'))

    const wrapper = await montarPainel()
    await flushPromises()

    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
  })
})
