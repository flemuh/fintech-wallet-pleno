const FORMATADOR_DE_DATA_E_HORA = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'short',
  timeStyle: 'short',
  timeZone: 'America/Sao_Paulo',
})

// A API devolve datas em UTC; a tela mostra no horário de Brasília
export function formatarDataEHora(dataIso: string): string {
  return FORMATADOR_DE_DATA_E_HORA.format(new Date(dataIso))
}

// Máscara do campo de data: só números, com as barras no lugar ("12112026" vira "12/11/2026")
export function mascararData(texto: string): string {
  const digitos = texto.replace(/\D/g, '').slice(0, 8)

  if (digitos.length <= 2) return digitos
  if (digitos.length <= 4) return `${digitos.slice(0, 2)}/${digitos.slice(2)}`

  return `${digitos.slice(0, 2)}/${digitos.slice(2, 4)}/${digitos.slice(4)}`
}

// "12/11/2026" vira "2026-11-12", o formato da API. Data incompleta ou inexistente (31/02) devolve null
export function dataBrParaIso(texto: string): string | null {
  const partes = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(texto)
  if (!partes) return null

  const [, dia, mes, ano] = partes
  const data = new Date(Date.UTC(Number(ano), Number(mes) - 1, Number(dia)))
  const existe =
    data.getUTCFullYear() === Number(ano) &&
    data.getUTCMonth() === Number(mes) - 1 &&
    data.getUTCDate() === Number(dia)

  return existe ? `${ano}-${mes}-${dia}` : null
}

// "2026-11-12" vira "12/11/2026"; qualquer outro texto vira vazio
export function dataIsoParaBr(dataIso: string): string {
  const partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(dataIso)

  return partes ? `${partes[3]}/${partes[2]}/${partes[1]}` : ''
}
