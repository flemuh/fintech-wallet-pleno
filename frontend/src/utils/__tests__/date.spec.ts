import { describe, expect, it } from 'vitest'

import { dataBrParaIso, dataIsoParaBr, formatarDataEHora, mascararData } from '@/utils/date'

describe('formatarDataEHora', () => {
  it('converte UTC para o horário de Brasília', () => {
    expect(formatarDataEHora('2026-06-15T15:30:00+00:00')).toMatch(/^15\/06\/2026,? 12:30$/)
  })

  it('volta para o dia anterior quando a hora em UTC já virou o dia', () => {
    expect(formatarDataEHora('2026-06-16T02:00:00+00:00')).toMatch(/^15\/06\/2026,? 23:00$/)
  })
})

describe('mascararData', () => {
  it.each([
    ['', ''],
    ['1', '1'],
    ['12', '12'],
    ['121', '12/1'],
    ['1211', '12/11'],
    ['12112026', '12/11/2026'],
    ['121120269', '12/11/2026'],
    ['12a/11-2026', '12/11/2026'],
  ])('"%s" vira "%s"', (digitado, esperado) => {
    expect(mascararData(digitado)).toBe(esperado)
  })
})

describe('dataBrParaIso', () => {
  it('converte dd/mm/aaaa para o formato da API', () => {
    expect(dataBrParaIso('12/11/2026')).toBe('2026-11-12')
  })

  it.each(['', '12/11', '12/11/26', '31/02/2026', '00/01/2026', '12/13/2026'])(
    'recusa "%s"',
    (texto) => {
      expect(dataBrParaIso(texto)).toBeNull()
    },
  )

  it('aceita 29/02 só em ano bissexto', () => {
    expect(dataBrParaIso('29/02/2028')).toBe('2028-02-29')
    expect(dataBrParaIso('29/02/2026')).toBeNull()
  })
})

describe('dataIsoParaBr', () => {
  it('converte o formato da API para dd/mm/aaaa', () => {
    expect(dataIsoParaBr('2026-11-12')).toBe('12/11/2026')
  })

  it('devolve vazio para texto fora do formato', () => {
    expect(dataIsoParaBr('')).toBe('')
    expect(dataIsoParaBr('12/11/2026')).toBe('')
  })
})
