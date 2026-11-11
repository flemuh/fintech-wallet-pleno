import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory } from 'vue-router'

import { armazenamentoDoToken } from '@/api/http'
import { criarRoteador } from '@/router'

function criarRoteadorDeTeste() {
  return criarRoteador(createMemoryHistory())
}

describe('guard de rotas', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('visitante é enviado ao login ao abrir a home', async () => {
    const roteador = criarRoteadorDeTeste()

    await roteador.push('/')

    expect(roteador.currentRoute.value.name).toBe('login')
  })

  it('usuário autenticado acessa a home', async () => {
    armazenamentoDoToken.salvar('token-de-teste')
    setActivePinia(createPinia())
    const roteador = criarRoteadorDeTeste()

    await roteador.push('/')

    expect(roteador.currentRoute.value.name).toBe('home')
  })

  it('usuário autenticado é enviado à home ao abrir o login', async () => {
    armazenamentoDoToken.salvar('token-de-teste')
    setActivePinia(createPinia())
    const roteador = criarRoteadorDeTeste()

    await roteador.push('/login')

    expect(roteador.currentRoute.value.name).toBe('home')
  })

  it('usuário autenticado é enviado à home ao abrir o registro', async () => {
    armazenamentoDoToken.salvar('token-de-teste')
    setActivePinia(createPinia())
    const roteador = criarRoteadorDeTeste()

    await roteador.push('/register')

    expect(roteador.currentRoute.value.name).toBe('home')
  })

  it('visitante acessa login e registro', async () => {
    const roteador = criarRoteadorDeTeste()

    await roteador.push('/login')
    expect(roteador.currentRoute.value.name).toBe('login')

    await roteador.push('/register')
    expect(roteador.currentRoute.value.name).toBe('register')
  })
})
