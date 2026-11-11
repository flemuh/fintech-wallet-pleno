import { describe, expect, it } from 'vitest'

import { formatarDataEHora } from '@/utils/date'

describe('formatarDataEHora', () => {
  it('converte UTC para o horário de Brasília', () => {
    expect(formatarDataEHora('2026-06-15T15:30:00+00:00')).toMatch(/^15\/06\/2026,? 12:30$/)
  })

  it('volta para o dia anterior quando a hora em UTC já virou o dia', () => {
    expect(formatarDataEHora('2026-06-16T02:00:00+00:00')).toMatch(/^15\/06\/2026,? 23:00$/)
  })
})
