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

// Máscara de real para o campo de valor: só os dígitos contam e entram da direita para a esquerda,
// então "1050" vira "10,50". O limite de 12 dígitos evita número maior que o JavaScript representa;
// o teto de verdade é validado pela API
export function mascararReais(texto: string): string {
  const digitos = texto.replace(/\D/g, '').slice(0, 12)

  if (digitos === '') return ''

  return (Number(digitos) / 100).toLocaleString('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}
