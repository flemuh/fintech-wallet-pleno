import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'

import ToastContainer from '@/components/ui/ToastContainer.vue'
import { useToast } from '@/composables/useToast'

describe('ToastContainer', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    useToast().limpar()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('mostra as mensagens dos toasts', async () => {
    const wrapper = mount(ToastContainer)

    useToast().sucesso('Depósito realizado')
    await nextTick()

    expect(wrapper.text()).toContain('Depósito realizado')
  })

  it('fecha o toast ao clicar no X', async () => {
    const wrapper = mount(ToastContainer)
    useToast().alerta('Sessão expirada')
    await nextTick()

    await wrapper.find('button').trigger('click')

    expect(wrapper.text()).not.toContain('Sessão expirada')
  })

  it('anuncia o erro com role alert e o sucesso com role status', async () => {
    const wrapper = mount(ToastContainer)

    useToast().erro('Falhou')
    useToast().sucesso('Salvo')
    await nextTick()

    expect(wrapper.findAll('[role="alert"]')).toHaveLength(1)
    expect(wrapper.findAll('[role="status"]')).toHaveLength(1)
  })
})
