const FORMATADOR_DE_DATA_E_HORA = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'short',
  timeStyle: 'short',
  timeZone: 'America/Sao_Paulo',
})

// A API devolve datas em UTC; a tela mostra no horário de Brasília
export function formatarDataEHora(dataIso: string): string {
  return FORMATADOR_DE_DATA_E_HORA.format(new Date(dataIso))
}
