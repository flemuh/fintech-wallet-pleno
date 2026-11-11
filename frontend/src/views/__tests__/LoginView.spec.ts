import { flushPromises, mount } from '@vue/test-utils'
import { AxiosError, AxiosHeaders } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import * as api from '@/api/auth'
import { useToast } from '@/composables/useToast'
import LoginView from '@/views/LoginView.vue'

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
      { path: '/login', name: 'login', component: LoginView },
      { path: '/register', name: 'register', component: { render: () => null } },
    ],
  })
  await roteador.push('/login')

  const tela = mount(LoginView, { global: { plugins: [pinia, roteador] } })
  return { tela, roteador }
}

async function preencherEEnviar(tela: Awaited<ReturnType<typeof montarTela>>['tela']) {
  await tela.find('#email').setValue('ana@exemplo.com')
  await tela.find('#password').setValue('senha1234')
  await tela.find('form').trigger('submit')
}

describe('tela de login', () => {
  beforeEach(() => {
    localStorage.clear()
    vi.resetAllMocks()
    useToast().limpar()
  })

  it('mostra o erro do campo quando a API recusa as credenciais', async () => {
    vi.mocked(api.entrar).mockRejectedValue(
      erroDaApi(422, {
        message: 'Credenciais inválidas.',
        code: 'validation_failed',
        errors: { email: ['Credenciais inválidas.'] },
      }),
    )
    const { tela } = await montarTela()

    await preencherEEnviar(tela)
    await flushPromises()

    expect(tela.text()).toContain('Credenciais inválidas.')
    expect(useToast().toasts.value).toHaveLength(0)
  })

  it('mostra alerta geral quando o erro não é de validação', async () => {
    vi.mocked(api.entrar).mockRejectedValue(
      erroDaApi(429, {
        message: 'Muitas requisições. Tente novamente em instantes.',
        code: 'too_many_requests',
      }),
    )
    const { tela } = await montarTela()

    await preencherEEnviar(tela)
    await flushPromises()

    expect(tela.find('[role="alert"]').text()).toContain('Muitas requisições')
  })

  it('login aceito leva para a página inicial com toast de boas-vindas', async () => {
    vi.mocked(api.entrar).mockResolvedValue({
      user: { id: 1, name: 'Ana', email: 'ana@exemplo.com' },
      token: 'token-123',
    })
    const { tela, roteador } = await montarTela()

    await preencherEEnviar(tela)
    await flushPromises()

    expect(roteador.currentRoute.value.name).toBe('home')
    expect(useToast().toasts.value.map((item) => [item.tipo, item.mensagem])).toEqual([
      ['sucesso', 'Bem-vindo, Ana.'],
    ])
  })

  it('desabilita o botão enquanto envia', async () => {
    vi.mocked(api.entrar).mockReturnValue(new Promise(() => {}))
    const { tela } = await montarTela()

    await preencherEEnviar(tela)

    expect(tela.find('button').attributes('disabled')).toBeDefined()
  })
})
