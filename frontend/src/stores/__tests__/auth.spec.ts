import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import * as api from '@/api/auth'
import { armazenamentoDoToken } from '@/api/http'
import { armazenamentoDoUsuario, useAuthStore } from '@/stores/auth'

vi.mock('@/api/auth')

const sessao = {
  user: { id: 1, name: 'Ana', email: 'ana@exemplo.com' },
  token: 'token-123',
}

describe('store de autenticação', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('entrar guarda o token e o usuário', async () => {
    vi.mocked(api.entrar).mockResolvedValue(sessao)
    const auth = useAuthStore()

    await auth.entrar({ email: 'ana@exemplo.com', password: 'senha1234' })

    expect(auth.autenticado).toBe(true)
    expect(auth.usuario?.name).toBe('Ana')
    expect(armazenamentoDoToken.obter()).toBe('token-123')
    expect(armazenamentoDoUsuario.obter()?.email).toBe('ana@exemplo.com')
  })

  it('registrar também abre a sessão', async () => {
    vi.mocked(api.registrar).mockResolvedValue(sessao)
    const auth = useAuthStore()

    await auth.registrar({ name: 'Ana', email: 'ana@exemplo.com', password: 'senha1234' })

    expect(auth.autenticado).toBe(true)
    expect(armazenamentoDoToken.obter()).toBe('token-123')
  })

  it('login recusado não abre sessão', async () => {
    vi.mocked(api.entrar).mockRejectedValue(new Error('422'))
    const auth = useAuthStore()

    await expect(auth.entrar({ email: 'ana@exemplo.com', password: 'errada' })).rejects.toThrow('422')

    expect(auth.autenticado).toBe(false)
    expect(armazenamentoDoToken.obter()).toBeNull()
  })

  it('sair limpa a sessão mesmo quando a API falha', async () => {
    vi.mocked(api.entrar).mockResolvedValue(sessao)
    vi.mocked(api.sair).mockRejectedValue(new Error('sem rede'))
    const auth = useAuthStore()
    await auth.entrar({ email: 'ana@exemplo.com', password: 'senha1234' })

    await auth.sair()

    expect(auth.autenticado).toBe(false)
    expect(auth.usuario).toBeNull()
    expect(armazenamentoDoToken.obter()).toBeNull()
    expect(armazenamentoDoUsuario.obter()).toBeNull()
  })

  it('retoma a sessão salva ao iniciar (recarregar a página)', () => {
    armazenamentoDoToken.salvar('token-123')
    armazenamentoDoUsuario.salvar(sessao.user)
    setActivePinia(createPinia())

    const auth = useAuthStore()

    expect(auth.autenticado).toBe(true)
    expect(auth.usuario?.name).toBe('Ana')
  })
})