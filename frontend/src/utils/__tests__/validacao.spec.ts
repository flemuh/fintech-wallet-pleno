import { describe, expect, it } from 'vitest'

import {
  avaliarSenha,
  emailValido,
  forcaDaSenha,
  senhaCabeNoLimite,
  senhaForte,
} from '@/utils/validacao'

describe('avaliarSenha', () => {
  it('marca cada critério separadamente', () => {
    expect(avaliarSenha('senha').map((criterio) => criterio.atendido)).toEqual([
      false,
      false,
      true,
      false,
      false,
    ])
    expect(avaliarSenha('Senha@1234').every((criterio) => criterio.atendido)).toBe(true)
  })

  it.each([
    ['', 'fraca'],
    ['senha', 'fraca'],
    ['Senha123', 'media'],
    ['Senha@1234', 'forte'],
  ] as const)('"%s" é uma senha %s', (senha, forca) => {
    expect(forcaDaSenha(senha)).toBe(forca)
  })

  it('só considera forte quando cumpre os 5 critérios', () => {
    expect(senhaForte('Senha1234')).toBe(false)
    expect(senhaForte('Senha@1234')).toBe(true)
  })
})

describe('emailValido', () => {
  it.each(['ana@exemplo.com', 'ana.souza+teste@empresa.com.br', "o'brien@exemplo.com.br"])(
    'aceita %s',
    (email) => {
      expect(emailValido(email)).toBe(true)
    },
  )

  it.each([
    '@emailqualquer',
    'ana@',
    'ana@exemplo',
    'ana souza@exemplo.com',
    'ana@@exemplo.com',
    '',
    'flemuh@gmail.com,,mmmm',
    'ana@ex_emplo.com',
    'ana@exemplo..com',
    'ana@-exemplo.com',
    'joão@exemplo.com',
    `${'a'.repeat(60)}@${'b'.repeat(36)}.com`,
  ])('recusa "%s"', (email) => {
    expect(emailValido(email)).toBe(false)
  })
})

describe('senhaCabeNoLimite', () => {
  it('conta bytes, não caracteres: acentos ocupam 2 bytes cada', () => {
    expect(senhaCabeNoLimite('a'.repeat(72))).toBe(true)
    expect(senhaCabeNoLimite('ç'.repeat(36))).toBe(true)
    expect(senhaCabeNoLimite('ç'.repeat(37))).toBe(false)
  })
})
