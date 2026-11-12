export interface CriterioDaSenha {
  rotulo: string
  atendido: boolean
}

export type ForcaDaSenha = 'fraca' | 'media' | 'forte'

// Mesmas regras do RegisterRequest no backend (Password::min(8)->mixedCase()->numbers()->symbols())
export function avaliarSenha(senha: string): CriterioDaSenha[] {
  return [
    { rotulo: 'Pelo menos 8 caracteres', atendido: senha.length >= 8 },
    { rotulo: 'Uma letra maiúscula', atendido: /\p{Lu}/u.test(senha) },
    { rotulo: 'Uma letra minúscula', atendido: /\p{Ll}/u.test(senha) },
    { rotulo: 'Um número', atendido: /\p{N}/u.test(senha) },
    { rotulo: 'Um símbolo, como @ # ! ?', atendido: /[\p{Z}\p{S}\p{P}]/u.test(senha) },
  ]
}

export function forcaDaSenha(senha: string): ForcaDaSenha {
  const atendidos = avaliarSenha(senha).filter((criterio) => criterio.atendido).length

  if (atendidos === 5) return 'forte'
  if (atendidos >= 3) return 'media'
  return 'fraca'
}

export function senhaForte(senha: string): boolean {
  return avaliarSenha(senha).every((criterio) => criterio.atendido)
}

// Mesmos limites dos Form Requests do backend
export const LIMITES = { nome: 100, email: 100, senha: 64, bytesDaSenha: 72 } as const

// O bcrypt só considera 72 bytes, e acentos e outros caracteres fora do ASCII ocupam mais de um byte
export function senhaCabeNoLimite(senha: string): boolean {
  return new TextEncoder().encode(senha).length <= LIMITES.bytesDaSenha
}

// Mesmo formato que o backend aceita (FILTER_VALIDATE_EMAIL)
const FORMATO_DO_EMAIL =
  /^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,}$/

export function emailValido(email: string): boolean {
  const texto = email.trim()

  return texto.length <= LIMITES.email && FORMATO_DO_EMAIL.test(texto)
}
