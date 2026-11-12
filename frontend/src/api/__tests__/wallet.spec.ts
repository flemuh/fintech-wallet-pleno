import type { AxiosResponse } from 'axios'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { http } from '@/api/http'
import { depositar, sacar } from '@/api/wallet'
import type { Transacao } from '@/api/wallet'

const transacao: Transacao = {
  id: 1,
  type: 'credit',
  amount: 1050,
  balance_after: 1050,
  created_at: '2026-11-12T12:00:00+00:00',
}

// Só o que a função lê da resposta do Axios
const resposta = { data: { data: transacao } } as AxiosResponse

describe('api da carteira', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('o depósito envia o valor e a chave de idempotência no cabeçalho', async () => {
    const post = vi.spyOn(http, 'post').mockResolvedValue(resposta)

    await expect(depositar(10.5, 'chave-1')).resolves.toEqual(transacao)

    expect(post).toHaveBeenCalledWith(
      '/wallet/deposits',
      { amount: 10.5 },
      { headers: { 'Idempotency-Key': 'chave-1' } },
    )
  })

  it('o saque usa a rota de saques com a mesma chave', async () => {
    const post = vi.spyOn(http, 'post').mockResolvedValue(resposta)

    await sacar(5, 'chave-2')

    expect(post).toHaveBeenCalledWith(
      '/wallet/withdrawals',
      { amount: 5 },
      { headers: { 'Idempotency-Key': 'chave-2' } },
    )
  })
})
