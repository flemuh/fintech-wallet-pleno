import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError, AxiosHeaders } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import * as api from '@/api/auth'
import { useToast } from '@/composables/useToast'
import RegisterView from '@/views/RegisterView.vue'

vi.mock('@/api/auth')

function erroDaApi(status: number, corpo: unknown): AxiosError {
  const config = { headers: new AxiosHeaders() }
  const resposta = { data: corpo, status, statusText: '', headers: {}, config }
  return new AxiosError('Falhou', 'ERR_BAD_REQUEST', config, null, resposta)
}

async function montarTela() {
  const pinia = createPinia()
  setActivePinia(pinia)

  const roteador = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: { render: () => null } },
      { path: '/login', name: 'login', component: { render: () => null } },
      { path: '/register', name: 'register', component: RegisterView },
    ],
  })
  await roteador.push('/register')

  const tela = mount(RegisterView, { global: { plugins: [pinia, roteador] } })
  return { tela, roteador }
}

async function preencherEEnviar(tela: Awaited<ReturnType<typeof montarTela>>['tela']) {
  await tela.find('#name').setValue('Ana Souza')
  await tela.find('#email').setValue('ana@exemplo.com')
  await tela.find('#password').setValue('senha1234')
  await tela.find('form').trigger('submit')
}

describe('tela de cadastro', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.resetAllMocks()
    useToast().limpar()
  })

  it('cadastro aceito leva para a página inicial com toast de boas-vindas', async () => {
    vi.mocked(api.registrar).mockResolvedValue({
      user: { id: 1, name: 'Ana Souza', email: 'ana@exemplo.com' },
      token: 'token-123',
    })
    const { tela, roteador } = await montarTela()

    await preencherEEnviar(tela)
    await flushPromises()

    expect(roteador.currentRoute.value.name).toBe('home')
    expect(useToast().toasts.value.map((item) => item.mensagem)).toEqual([
      'Conta criada. Bem-vindo, Ana Souza.',
    ])
  })

  it('mostra o erro de cada campo quando a API recusa', async () => {
    vi.mocked(api.registrar).mockRejectedValue(
      erroDaApi(422, {
        message: 'O e-mail já está em uso.',
        code: 'validation_failed',
        errors: {
          email: ['O e-mail já está em uso.'],
          password: ['A senha deve conter pelo menos um número.'],
        },
      }),
    )
    const { tela, roteador } = await montarTela()

    await preencherEEnviar(tela)
    await flushPromises()

    expect(roteador.currentRoute.value.name).toBe('register')
    expect(tela.text()).toContain('O e-mail já está em uso.')
    expect(tela.text()).toContain('A senha deve conter pelo menos um número.')
  })

  it('desabilita o botão enquanto envia', async () => {
    vi.mocked(api.registrar).mockReturnValue(new Promise(() => {}))
    const { tela } = await montarTela()

    await preencherEEnviar(tela)

    expect(tela.find('button').attributes('disabled')).toBeDefined()
  })
})
