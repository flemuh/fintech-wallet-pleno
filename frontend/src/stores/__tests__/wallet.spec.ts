import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import * as api from '@/api/wallet'
import { useWalletStore } from '@/stores/wallet'

vi.mock('@/api/wallet')

const resumo = { balance: 49610, month_credit: 28575, month_debit: 15325 }

const transacao = {
  id: 1,
  type: 'credit' as const,
  amount: 1000,
  balance_after: 1000,
  created_at: '2026-06-15T15:30:00+00:00',
}

const pagina = {
  data: [transacao],
  meta: { current_page: 1, last_page: 1, per_page: 5, total: 1 },
}

describe('store da carteira', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('carrega o resumo e as últimas cinco transações', async () => {
    vi.mocked(api.buscarResumo).mockResolvedValue(resumo)
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
    const wallet = useWalletStore()

    await wallet.carregarPainel()

    expect(wallet.resumo).toEqual(resumo)
    expect(wallet.recentes).toEqual(pagina.data)
    expect(wallet.erro).toBeNull()
    expect(wallet.carregando).toBe(false)
    expect(api.listarTransacoes).toHaveBeenCalledWith({ per_page: 5 })
  })

  it('guarda a mensagem de erro quando a API falha', async () => {
    vi.mocked(api.buscarResumo).mockRejectedValue(new Error('falhou'))
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
    const wallet = useWalletStore()

    await wallet.carregarPainel()

    expect(wallet.erro).not.toBeNull()
    expect(wallet.resumo).toBeNull()
    expect(wallet.carregando).toBe(false)
  })

  it('deposita e recarrega o painel com o novo saldo', async () => {
    vi.mocked(api.depositar).mockResolvedValue(transacao)
    vi.mocked(api.buscarResumo).mockResolvedValue(resumo)
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
    const wallet = useWalletStore()

    await wallet.depositar(10.5)

    expect(api.depositar).toHaveBeenCalledWith(10.5)
    expect(wallet.resumo).toEqual(resumo)
    expect(wallet.recentes).toEqual(pagina.data)
  })

  it('não recarrega o painel quando o depósito falha', async () => {
    vi.mocked(api.depositar).mockRejectedValue(new Error('falhou'))
    const wallet = useWalletStore()

    await expect(wallet.depositar(10)).rejects.toThrow('falhou')

    expect(api.buscarResumo).not.toHaveBeenCalled()
  })

  it('saca e recarrega o painel com o novo saldo', async () => {
    vi.mocked(api.sacar).mockResolvedValue({ ...transacao, type: 'debit' })
    vi.mocked(api.buscarResumo).mockResolvedValue(resumo)
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
    const wallet = useWalletStore()

    await wallet.sacar(5.25)

    expect(api.sacar).toHaveBeenCalledWith(5.25)
    expect(wallet.resumo).toEqual(resumo)
  })

  it('limpa os dados ao sair', async () => {
    vi.mocked(api.buscarResumo).mockResolvedValue(resumo)
    vi.mocked(api.listarTransacoes).mockResolvedValue(pagina)
    const wallet = useWalletStore()
    await wallet.carregarPainel()

    wallet.limpar()

    expect(wallet.resumo).toBeNull()
    expect(wallet.recentes).toEqual([])
  })
})
