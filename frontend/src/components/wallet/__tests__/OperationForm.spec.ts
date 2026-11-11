import { describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'

import OperationForm from '@/components/wallet/OperationForm.vue'

type Executar = (valorEmReais: number) => Promise<unknown>

function montarFormulario(executar: Executar) {
  return mount(OperationForm, {
    props: {
      prefixoDoId: 'deposito',
      titulo: 'Depositar',
      rotuloDoBotao: 'Depositar',
      mensagemDeSucesso: 'Depósito realizado com sucesso.',
      executar,
    },
  })
}

async function enviarValor(wrapper: ReturnType<typeof montarFormulario>, texto: string) {
  await wrapper.find('input').setValue(texto)
  await wrapper.find('form').trigger('submit')
}

describe('OperationForm', () => {
  it('envia o valor em reais, mostra o sucesso e limpa o campo', async () => {
    const executar = vi.fn<Executar>().mockResolvedValue(undefined)
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, '10,50')
    await flushPromises()

    expect(executar).toHaveBeenCalledWith(10.5)
    expect(wrapper.text()).toContain('Depósito realizado com sucesso.')
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

  it('mostra um alerta quando a operação falha', async () => {
    const executar = vi.fn<Executar>().mockRejectedValue(new Error('falhou'))
    const wrapper = montarFormulario(executar)

    await enviarValor(wrapper, '10')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Depósito realizado com sucesso.')
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
})
