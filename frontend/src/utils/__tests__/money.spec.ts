import { describe, expect, it } from 'vitest'

import { formatarCentavos, lerValorEmReais } from '@/utils/money'

describe('formatarCentavos', () => {
  it('formata centavos em reais no padrão brasileiro', () => {
    expect(formatarCentavos(123456)).toBe('R$\u00a01.234,56')
    expect(formatarCentavos(5)).toBe('R$\u00a00,05')
    expect(formatarCentavos(0)).toBe('R$\u00a00,00')
  })
})

describe('lerValorEmReais', () => {
  it('converte valores válidos em centavos', () => {
    expect(lerValorEmReais('10')).toBe(1000)
    expect(lerValorEmReais('10,5')).toBe(1050)
    expect(lerValorEmReais('10,50')).toBe(1050)
    expect(lerValorEmReais('0,01')).toBe(1)
    expect(lerValorEmReais('1.234,56')).toBe(123456)
    expect(lerValorEmReais('  25,90 ')).toBe(2590)
  })

  it('devolve null para texto inválido', () => {
    expect(lerValorEmReais('')).toBeNull()
    expect(lerValorEmReais('abc')).toBeNull()
    expect(lerValorEmReais('10,555')).toBeNull()
    expect(lerValorEmReais('10.50')).toBeNull()
    expect(lerValorEmReais('-5')).toBeNull()
    expect(lerValorEmReais('1,2,3')).toBeNull()
  })
})
