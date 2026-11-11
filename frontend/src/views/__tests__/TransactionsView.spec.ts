import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory } from 'vue-router'

import { armazenamentoDoToken } from '@/api/http'
import * as api from '@/api/wallet'
import type { PaginaDeTransacoes } from '@/api/wallet'
import { criarRoteador } from '@/router'
import TransactionsView from '@/views/TransactionsView.vue'

vi.mock('@/api/wallet')

const pagina: PaginaDeTransacoes = {
  data: [
    {
      id: 1,
      type: 'debit',
      amount: 500,
      balance_after: 550,
      created_at: '2026-06-15T16:00:00+00:00',
    },
  ],
  meta: { current_page: 1, last_page: 3, per_page: 10, total: 25 },
}

async function montarTela(rotaInicial: string) {
  armazenamentoDoToken.salvar('token-de-teste')
  const pinia = createPinia()
  setActivePinia(pinia)
  const roteador = criarRoteador(createMemoryHistory())
  await roteador.push(rotaInicial)

  const wrapper = mount(TransactionsView, { global: { plugins: [pinia, roteador] } })
  await flushPromises()

  return { wrapper, roteador }
}

async function clicarNoBotao(wrapper: Awaited<ReturnType<typeof montarTela>>['wrapper'], texto: string) {
  await wrapper
    .findAll('button')
    .find((botao) => botao.text() === texto)
    ?.trigger('click')
  await flushPromises()
}

describe('TransactionsView', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.resetAllMocks()
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
  })

  it('carrega a página usando os filtros da URL', async () => {
    const { wrapper } = await montarTela('/transactions?type=debit&page=2')

    expect(api.listarTransacoes).toHaveBeenCalledWith({ type: 'debit', page: 2, per_page: 10 })
    expect(wrapper.find<HTMLSelectElement>('select').element.value).toBe('debit')
    expect(wrapper.text()).toContain('Saque')
  })

  it('aplica os filtros na URL e busca de novo', async () => {
    const { wrapper, roteador } = await montarTela('/transactions')

    await wrapper.find('select').setValue('credit')
    await wrapper.find('#filtro-inicio').setValue('2026-06-01')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(roteador.currentRoute.value.query).toEqual({ type: 'credit', from: '2026-06-01' })
    expect(api.listarTransacoes).toHaveBeenLastCalledWith({
      type: 'credit',
      from: '2026-06-01',
      per_page: 10,
    })
  })

  it('troca de página mantendo os filtros', async () => {
    const { wrapper, roteador } = await montarTela('/transactions?type=debit')

    await clicarNoBotao(wrapper, 'Próxima')

    expect(roteador.currentRoute.value.query).toEqual({ type: 'debit', page: '2' })
    expect(api.listarTransacoes).toHaveBeenLastCalledWith({ type: 'debit', page: 2, per_page: 10 })
  })

  it('limpa os filtros', async () => {
    const { wrapper, roteador } = await montarTela('/transactions?type=debit')

    await clicarNoBotao(wrapper, 'Limpar')

    expect(roteador.currentRoute.value.query).toEqual({})
    expect(api.listarTransacoes).toHaveBeenLastCalledWith({ per_page: 10 })
  })

  it('mostra um alerta quando a API falha', async () => {
    vi.mocked(api.listarTransacoes).mockRejectedValue(new Error('falhou'))

    const { wrapper } = await montarTela('/transactions')

    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
  })
})
