import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import AppDateField from '@/components/ui/AppDateField.vue'

function montar(valorInicial = '') {
  const campo = mount(AppDateField, {
    props: {
      id: 'data',
      label: 'Data inicial',
      modelValue: valorInicial,
      'onUpdate:modelValue': (novo: string) => campo.setProps({ modelValue: novo }),
    },
  })

  return campo
}

describe('AppDateField', () => {
  afterEach(() => {
    // O jsdom não tem showPicker: o teste que o cria apaga depois
    delete (HTMLInputElement.prototype as Partial<HTMLInputElement>).showPicker
  })

  it('mascara a digitação em dd/mm/aaaa', async () => {
    const campo = montar()

    await campo.find('#data').setValue('12112026')

    expect(campo.props('modelValue')).toBe('12/11/2026')
  })

  it('a data escolhida no calendário volta para o campo em dd/mm/aaaa', async () => {
    const campo = montar()
    const calendario = campo.find<HTMLInputElement>('#data-calendario')

    calendario.element.value = '2026-11-12'
    await calendario.trigger('change')

    expect(campo.props('modelValue')).toBe('12/11/2026')
  })

  it('o calendário abre já na data digitada', () => {
    const campo = montar('12/11/2026')

    expect(campo.find<HTMLInputElement>('#data-calendario').element.value).toBe('2026-11-12')
  })

  it('o botão abre o seletor de datas do navegador', async () => {
    const showPicker = vi.fn<() => void>()
    HTMLInputElement.prototype.showPicker = showPicker
    const campo = montar()

    await campo.find('button[aria-label="Abrir calendário: Data inicial"]').trigger('click')

    expect(showPicker).toHaveBeenCalledOnce()
  })

  it('sem showPicker no navegador, o clique não quebra a tela', async () => {
    const campo = montar()

    await campo.find('button[aria-label="Abrir calendário: Data inicial"]').trigger('click')

    expect(campo.find('#data').exists()).toBe(true)
  })
})
