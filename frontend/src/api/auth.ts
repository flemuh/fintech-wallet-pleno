import { http } from '@/api/http'

export interface Usuario {
  id: number
  name: string
  email: string
}

export interface CredenciaisDeLogin {
  email: string
  password: string
}

export interface DadosDeRegistro extends CredenciaisDeLogin {
  name: string
}

export interface Sessao {
  user: Usuario
  token: string
}

export async function registrar(dados: DadosDeRegistro): Promise<Sessao> {
  const { data } = await http.post<Sessao>('/auth/register', dados)
  return data
}

export async function entrar(credenciais: CredenciaisDeLogin): Promise<Sessao> {
  const { data } = await http.post<Sessao>('/auth/login', credenciais)
  return data
}

export async function sair(): Promise<void> {
  await http.post('/auth/logout')
}