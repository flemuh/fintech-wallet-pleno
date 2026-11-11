import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppPagination from '@/components/ui/AppPagination.vue'

function botao(wrapper: ReturnType<typeof mount>, texto: string) {
  return wrapper.findAll('button').find((item) => item.text() === texto)
}

describe('AppPagination', () => {
  it('não aparece quando há uma página só', () => {
    const wrapper = mount(AppPagination, { props: { paginaAtual: 1, ultimaPagina: 1 } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })

  it('emite a página seguinte e a anterior', async () => {
    const wrapper = mount(AppPagination, { props: { paginaAtual: 2, ultimaPagina: 3 } })

    await botao(wrapper, 'Próxima')?.trigger('click')
    await botao(wrapper, 'Anterior')?.trigger('click')

    expect(wrapper.emitted('mudar')).toEqual([[3], [1]])
  })

  it('desabilita os botões nas pontas', () => {
    const primeira = mount(AppPagination, { props: { paginaAtual: 1, ultimaPagina: 3 } })
    const ultima = mount(AppPagination, { props: { paginaAtual: 3, ultimaPagina: 3 } })

    expect(botao(primeira, 'Anterior')?.attributes('disabled')).toBeDefined()
    expect(botao(ultima, 'Próxima')?.attributes('disabled')).toBeDefined()
  })
})
