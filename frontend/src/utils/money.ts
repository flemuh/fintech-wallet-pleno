const FORMATADOR_DE_REAIS = new Intl.NumberFormat('pt-BR', {
  style: 'currency',
  currency: 'BRL',
})

// Aceita o padrão brasileiro: milhar com ponto (opcional) e até duas casas depois da vírgula
const PADRAO_DE_VALOR = /^(\d{1,3}(\.\d{3})+|\d+)(,\d{1,2})?$/

export function formatarCentavos(centavos: number): string {
  return FORMATADOR_DE_REAIS.format(centavos / 100)
}

export function lerValorEmReais(texto: string): number | null {
  const limpo = texto.trim()

  if (!PADRAO_DE_VALOR.test(limpo)) return null

  const [parteInteira = '', parteDecimal = ''] = limpo.replace(/\./g, '').split(',')
  const centavos = Number(parteInteira) * 100 + Number(parteDecimal.padEnd(2, '0'))

  return Number.isSafeInteger(centavos) ? centavos : null
}

export function centavosParaValorDaApi(centavos: number): string {
  const reais = Math.floor(centavos / 100)
  const resto = String(centavos % 100).padStart(2, '0')

  return `${reais}.${resto}`
}
