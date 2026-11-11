import { describe, expect, it } from 'vitest'

import { lerValorEmReais, mascararReais } from '@/utils/money'

describe('mascararReais', () => {
  it.each([
    ['', ''],
    ['1', '0,01'],
    ['10', '0,10'],
    ['1050', '10,50'],
    ['123456', '1.234,56'],
    ['10,50', '10,50'],
  ])('transforma "%s" em "%s"', (digitado, esperado) => {
    expect(mascararReais(digitado)).toBe(esperado)
  })

  it('descarta letras e símbolos', () => {
    expect(mascararReais('abc')).toBe('')
    expect(mascararReais('R$ 12a3')).toBe('1,23')
  })

  it('limita a 12 dígitos', () => {
    expect(mascararReais('1234567890123456')).toBe('1.234.567.890,12')
  })
})

describe('lerValorEmReais com entradas inválidas ou maliciosas', () => {
  it.each([
    'abc',
    '',
    '-10',
    '1e5',
    '10,555',
    '1,2,3',
    '1.23',
    '10,50 reais',
    'R$ 10,50',
    '<script>alert(1)</script>',
    "10'; DROP TABLE users;--",
  ])('rejeita "%s"', (entrada) => {
    expect(lerValorEmReais(entrada)).toBeNull()
  })

  it('aceita o que a máscara produz', () => {
    expect(lerValorEmReais(mascararReais('1050'))).toBe(1050)
    expect(lerValorEmReais(mascararReais('123456'))).toBe(123456)
  })
})
