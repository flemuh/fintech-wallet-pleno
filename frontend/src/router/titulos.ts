const NOME_DO_APP = 'Fintech Wallet'

const TITULOS: Record<string, string> = {
  home: 'Minha carteira',
  transactions: 'Extrato',
  login: 'Entrar',
  register: 'Criar conta',
}

// Título da aba do navegador para cada rota; rota sem título mostra só o nome do app
export function tituloDaPagina(nomeDaRota: unknown): string {
  const titulo = typeof nomeDaRota === 'string' ? TITULOS[nomeDaRota] : undefined

  return titulo ? `${NOME_DO_APP} · ${titulo}` : NOME_DO_APP
}
