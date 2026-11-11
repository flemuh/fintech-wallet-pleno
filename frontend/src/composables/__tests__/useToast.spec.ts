import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { useToast } from '@/composables/useToast'

describe('useToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    useToast().limpar()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('mostra o toast com o tipo e a mensagem', () => {
    const toast = useToast()

    toast.sucesso('Salvo')
    toast.erro('Falhou')

    expect(toast.toasts.value.map((item) => [item.tipo, item.mensagem])).toEqual([
      ['sucesso', 'Salvo'],
      ['erro', 'Falhou'],
    ])
  })

  it('todos os tipos somem sozinhos depois de 3 segundos', () => {
    const toast = useToast()

    toast.sucesso('Salvo')
    toast.erro('Falhou')
    toast.alerta('Atenção')

    vi.advanceTimersByTime(2999)
    expect(toast.toasts.value).toHaveLength(3)

    vi.advanceTimersByTime(1)
    expect(toast.toasts.value).toHaveLength(0)
  })

  it('remove o toast pelo id', () => {
    const toast = useToast()

    toast.info('Olá')
    const [primeiro] = toast.toasts.value
    toast.remover(primeiro?.id ?? -1)

    expect(toast.toasts.value).toHaveLength(0)
  })
})
