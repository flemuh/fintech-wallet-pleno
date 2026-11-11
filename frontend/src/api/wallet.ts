import { http } from '@/api/http'

export type TipoDeTransacao = 'credit' | 'debit'

/** Valores em centavos. */
export interface Transacao {
  id: number
  type: TipoDeTransacao
  amount: number
  balance_after: number
  created_at: string
}

/** Valores em centavos; os totais são do mês atual. */
export interface ResumoDaCarteira {
  balance: number
  month_credit: number
  month_debit: number
}

export interface FiltrosDoHistorico {
  type?: TipoDeTransacao
  from?: string
  to?: string
  page?: number
  per_page?: number
}

export interface PaginaDeTransacoes {
  data: Transacao[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export async function buscarResumo(): Promise<ResumoDaCarteira> {
  const { data } = await http.get<{ data: ResumoDaCarteira }>('/wallet')
  return data.data
}

export async function listarTransacoes(
  filtros: FiltrosDoHistorico = {},
): Promise<PaginaDeTransacoes> {
  const { data } = await http.get<PaginaDeTransacoes>('/wallet/transactions', { params: filtros })
  return data
}

/** O valor enviado é em reais (ex.: 10.5); a API converte para centavos. */
export async function depositar(valorEmReais: number): Promise<Transacao> {
  const { data } = await http.post<{ data: Transacao }>('/wallet/deposits', {
    amount: valorEmReais,
  })
  return data.data
}

/** O valor enviado é em reais (ex.: 10.5); a API converte para centavos. */
export async function sacar(valorEmReais: number): Promise<Transacao> {
  const { data } = await http.post<{ data: Transacao }>('/wallet/withdrawals', {
    amount: valorEmReais,
  })
  return data.data
}