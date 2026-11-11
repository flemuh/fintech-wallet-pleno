import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'

import OperationForm from '@/components/wallet/OperationForm.vue'
import { useToast } from '@/composables/useToast'

type Executar = (valorEmReais: number) => Promise<unknown>

function montarFormulario(executar: Executar, saldoDisponivel?: number) {
  return mount(OperationForm, {
    props: {
      prefixoDoId: 'operacao',
      titulo: 'Operação',
      rotuloDoBotao: 'Confirmar',
      mensagemDeSucesso: 'Operação realizada com sucesso.',
      executar,
      saldoDisponivel,
    },
  })
}

async function enviarValor(wrapper: ReturnType<typeof montarFormulario>, texto: string) {
  await wrapper.find('input').setValue(texto)
  await wrapper.find('form').trigger('submit')
}

describe('OperationForm', () => {
  beforeEach(() => {
    useToast().limpar()
  })

  it('envia o valor em reais, avisa o sucesso com um toast e limpa o campo', async () => {
    const executar = vi.fn<Executar>().mockResolvedValue(undefined)
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, '10,50')
    await flushPromises()

    expect(executar).toHaveBeenCalledWith(10.5)
    expect(useToast().toasts.value.map((toast) => [toast.tipo, toast.mensagem])).toEqual([
      ['sucesso', 'Operação realizada com sucesso.'],
    ])
    expect(wrapper.find('input').element.value).toBe('')
  })

  it('não envia valor inválido e mostra o erro no campo', async () => {
    const executar = vi.fn<Executar>().mockResolvedValue(undefined)
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, 'abc')
    await flushPromises()

    expect(executar).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Informe um valor maior que zero')
  })

  it('avisa a falha da operação com um toast de erro', async () => {
    const executar = vi.fn<Executar>().mockRejectedValue(new Error('falhou'))
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, '10')
    await flushPromises()

    expect(useToast().toasts.value.map((toast) => toast.tipo)).toEqual(['erro'])
  })

  it('desabilita o botão enquanto envia', async () => {
    let concluir: () => void = () => {}
    const executar = vi.fn<Executar>(
      () =>
        new Promise<void>((resolve) => {
          concluir = resolve
        }),
    )
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, '10')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()

    concluir()
    await flushPromises()
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined()
  })

  it('bloqueia o valor acima do saldo sem chamar a API', async () => {
    const executar = vi.fn<Executar>().mockResolvedValue(undefined)
    const wrapper = montarFormulario(executar, 1000)

    await enviarValor(wrapper, '10,01')
    await flushPromises()

    expect(wrapper.text()).toContain('Saldo insuficiente')
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    expect(executar).not.toHaveBeenCalled()
  })

  it('permite sacar exatamente o saldo disponível', async () => {
    const executar = vi.fn<Executar>().mockResolvedValue(undefined)
    const wrapper = montarFormulario(executar, 1000)

    await enviarValor(wrapper, '10,00')
    await flushPromises()

    expect(wrapper.text()).not.toContain('Saldo insuficiente')
    expect(executar).toHaveBeenCalledWith(10)
  })
})
