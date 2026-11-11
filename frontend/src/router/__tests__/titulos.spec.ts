import { describe, expect, it } from 'vitest'

import { tituloDaPagina } from '@/router/titulos'

describe('tituloDaPagina', () => {
  it('monta o título com o nome do app e da tela', () => {
    expect(tituloDaPagina('home')).toBe('Fintech Wallet · Minha carteira')
    expect(tituloDaPagina('transactions')).toBe('Fintech Wallet · Extrato')
  })

  it('usa só o nome do app quando a rota não tem título', () => {
    expect(tituloDaPagina(undefined)).toBe('Fintech Wallet')
    expect(tituloDaPagina('desconhecida')).toBe('Fintech Wallet')
  })
})
