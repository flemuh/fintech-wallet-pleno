import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppField from '@/components/ui/AppField.vue'
import { mascararReais } from '@/utils/money'

function montarCampo(mascara?: (texto: string) => string) {
  return mount(AppField, {
    props: { id: 'campo', label: 'Valor', modelValue: '', mascara },
  })
}

describe('AppField', () => {
  it('sem máscara grava o texto como foi digitado', async () => {
    const wrapper = montarCampo()
    const campo = wrapper.find('input')

    await campo.setValue('abc')

    expect(campo.element.value).toBe('abc')
    expect(wrapper.emitted('update:modelValue')).toEqual([['abc']])
  })

  it('com máscara de reais não deixa letras ficarem no campo', async () => {
    const wrapper = montarCampo(mascararReais)
    const campo = wrapper.find('input')

    await campo.setValue('tgg')

    expect(campo.element.value).toBe('')
  })

  it('com máscara de reais formata os dígitos digitados', async () => {
    const wrapper = montarCampo(mascararReais)
    const campo = wrapper.find('input')

    await campo.setValue('1050')

    expect(campo.element.value).toBe('10,50')
    expect(wrapper.emitted('update:modelValue')).toEqual([['10,50']])
  })
})
