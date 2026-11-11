import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'

import * as api from '@/api/wallet'
import DashboardView from '@/views/DashboardView.vue'

vi.mock('@/api/wallet')

function montarPainel() {
  return mount(DashboardView, { global: { plugins: [createPinia()] } })
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

    const wrapper = montarPainel()
    await flushPromises()

    expect(wrapper.text()).toContain('R$\u00a0496,10')
    expect(wrapper.text()).toContain('R$\u00a0285,75')
    expect(wrapper.text()).toContain('R$\u00a0153,25')
    expect(wrapper.text()).toContain('Depósito')
  })

  it('mostra um alerta quando a API falha', async () => {
    vi.mocked(api.buscarResumo).mockRejectedValue(new Error('falhou'))
    vi.mocked(api.listarTransacoes).mockRejectedValue(new Error('falhou'))

    const wrapper = montarPainel()
    await flushPromises()

    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
  })
})
