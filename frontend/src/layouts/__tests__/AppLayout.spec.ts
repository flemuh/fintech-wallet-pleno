import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

async function montar() {
  const pinia = createPinia()
  setActivePinia(pinia)
  useAuthStore().usuario = { id: 1, name: 'Ana Souza', email: 'ana@exemplo.com' }

  const roteador = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: { render: () => null } },
      { path: '/transactions', name: 'transactions', component: { render: () => null } },
      { path: '/login', name: 'login', component: { render: () => null } },
    ],
  })
  await roteador.push('/')

  const tela = mount(AppLayout, { global: { plugins: [pinia, roteador] } })
  return { tela, roteador }
}

describe('AppLayout', () => {
  beforeEach(() => localStorage.clear())

  it('começa com o menu do celular fechado', async () => {
    const { tela } = await montar()

    expect(tela.find('#menu-mobile').exists()).toBe(false)
    expect(tela.find('button[aria-label="Abrir menu"]').attributes('aria-expanded')).toBe('false')
  })

  it('abre e fecha o menu pelo botão hambúrguer', async () => {
    const { tela } = await montar()

    await tela.find('button[aria-label="Abrir menu"]').trigger('click')
    expect(tela.find('#menu-mobile').exists()).toBe(true)
    expect(tela.find('#menu-mobile').text()).toContain('Extrato')

    await tela.find('button[aria-label="Fechar menu"]').trigger('click')
    expect(tela.find('#menu-mobile').exists()).toBe(false)
  })

  it('fecha o menu ao navegar para outra tela', async () => {
    const { tela, roteador } = await montar()

    await tela.find('button[aria-label="Abrir menu"]').trigger('click')
    await roteador.push('/transactions')
    await flushPromises()

    expect(tela.find('#menu-mobile').exists()).toBe(false)
  })
})
