import { AxiosError } from 'axios'
import type { AxiosAdapter, InternalAxiosRequestConfig } from 'axios'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { armazenamentoDoToken, http, lerErroDaApi, registrarAoExpirarSessao } from '@/api/http'

/** Adaptador falso: responde sem rede e guarda a requisição que chegou. */
function simularApi(status: number, corpo: unknown = {}) {
  const requisicoes: InternalAxiosRequestConfig[] = []

  const adaptador: AxiosAdapter = async (config) => {
    requisicoes.push(config)
    const resposta = { data: corpo, status, statusText: '', headers: {}, config }

    if (status >= 400) {
      throw new AxiosError('Falhou', 'ERR_BAD_REQUEST', config, null, resposta)
    }

    return resposta
  }

  http.defaults.adapter = adaptador
  return requisicoes
}

describe('cliente http', () => {
  beforeEach(() => {
    localStorage.clear()
    registrarAoExpirarSessao(() => {})
  })

  it('envia o token salvo no cabeçalho Authorization', async () => {
    armazenamentoDoToken.salvar('abc123')
    const requisicoes = simularApi(200)

    await http.get('/wallet')

    expect(requisicoes[0]?.headers.Authorization).toBe('Bearer abc123')
  })

  it('não envia Authorization quando não há token', async () => {
    const requisicoes = simularApi(200)

    await http.get('/health')

    expect(requisicoes[0]?.headers.Authorization).toBeUndefined()
  })

  it('401 com token salvo limpa a sessão e avisa quem se registrou', async () => {
    armazenamentoDoToken.salvar('expirado')
    const aviso = vi.fn<() => void>()
    registrarAoExpirarSessao(aviso)
    simularApi(401, { message: 'Não autenticado.', code: 'unauthenticated' })

    await expect(http.get('/wallet')).rejects.toBeTruthy()

    expect(armazenamentoDoToken.obter()).toBeNull()
    expect(aviso).toHaveBeenCalledOnce()
  })

  it('401 sem token salvo não dispara o aviso de sessão expirada', async () => {
    const aviso = vi.fn<() => void>()
    registrarAoExpirarSessao(aviso)
    simularApi(401, { message: 'Não autenticado.', code: 'unauthenticated' })

    await expect(http.get('/wallet')).rejects.toBeTruthy()

    expect(aviso).not.toHaveBeenCalled()
  })
})

describe('lerErroDaApi', () => {
  it('devolve message, code e errors do formato da API', async () => {
    simularApi(422, {
      message: 'O campo valor é obrigatório.',
      code: 'validation_failed',
      errors: { amount: ['O campo valor é obrigatório.'] },
    })

    const erro = await http.post('/wallet/deposits', {}).catch((e: unknown) => e)

    expect(lerErroDaApi(erro)).toEqual({
      message: 'O campo valor é obrigatório.',
      code: 'validation_failed',
      errors: { amount: ['O campo valor é obrigatório.'] },
    })
  })

  it('trata falha sem resposta da API como erro de rede', () => {
    const erro = lerErroDaApi(new Error('Network Error'))

    expect(erro.code).toBe('network_error')
    expect(erro.errors).toEqual({})
  })
})