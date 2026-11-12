import axios from 'axios'

const CHAVE_DO_TOKEN = 'wallet_token'

/** Formato de erro da API: toda resposta de erro traz message e code; a validação acrescenta errors. */
export interface ErroDaApi {
  message: string
  code: string
  errors: Record<string, string[]>
}

export const armazenamentoDoToken = {
  obter: (): string | null => localStorage.getItem(CHAVE_DO_TOKEN),
  salvar: (token: string): void => localStorage.setItem(CHAVE_DO_TOKEN, token),
  limpar: (): void => localStorage.removeItem(CHAVE_DO_TOKEN),
}

export const http = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

let aoExpirarSessao: (() => void) | null = null

/**
 * Define o que fazer quando a API recusa o token salvo (sessão expirada ou revogada).
 * Quem decide é o app (voltar ao login): esta camada não conhece router nem store.
 */
export function registrarAoExpirarSessao(acao: () => void): void {
  aoExpirarSessao = acao
}

http.interceptors.request.use((config) => {
  const token = armazenamentoDoToken.obter()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

http.interceptors.response.use(
  (resposta) => resposta,
  (erro: unknown) => {
    // 401 só significa "sessão perdida" quando havia um token salvo
    if (axios.isAxiosError(erro) && erro.response?.status === 401 && armazenamentoDoToken.obter()) {
      armazenamentoDoToken.limpar()
      aoExpirarSessao?.()
    }
    return Promise.reject(erro)
  },
)

/** Normaliza qualquer falha de requisição no formato de erro da API. */
export function lerErroDaApi(erro: unknown): ErroDaApi {
  if (axios.isAxiosError<Partial<ErroDaApi>>(erro) && erro.response?.data?.code) {
    const { message, code, errors } = erro.response.data
    return { message: message ?? 'Erro inesperado.', code, errors: errors ?? {} }
  }

  // Sem resposta no formato da API: rede fora do ar, servidor caindo, 502 do proxy
  return {
    message: 'Não foi possível falar com o servidor. Tente novamente.',
    code: 'network_error',
    errors: {},
  }
}
