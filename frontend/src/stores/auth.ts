import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import * as api from '@/api/auth'
import type { CredenciaisDeLogin, DadosDeRegistro, Sessao, Usuario } from '@/api/auth'
import { armazenamentoDoToken } from '@/api/http'

const CHAVE_DO_USUARIO = 'wallet_usuario'

export const armazenamentoDoUsuario = {
  obter(): Usuario | null {
    const texto = localStorage.getItem(CHAVE_DO_USUARIO)
    if (!texto) return null

    try {
      return JSON.parse(texto) as Usuario
    } catch {
      return null
    }
  },
  salvar: (usuario: Usuario): void => localStorage.setItem(CHAVE_DO_USUARIO, JSON.stringify(usuario)),
  limpar: (): void => localStorage.removeItem(CHAVE_DO_USUARIO),
}

export const useAuthStore = defineStore('auth', () => {
  // A sessão sobrevive ao recarregar a página: começa do que está salvo no navegador
  const token = ref<string | null>(armazenamentoDoToken.obter())
  const usuario = ref<Usuario | null>(armazenamentoDoUsuario.obter())

  const autenticado = computed(() => token.value !== null)

  function iniciarSessao(sessao: Sessao): void {
    token.value = sessao.token
    usuario.value = sessao.user
    armazenamentoDoToken.salvar(sessao.token)
    armazenamentoDoUsuario.salvar(sessao.user)
  }

  function limparSessao(): void {
    token.value = null
    usuario.value = null
    armazenamentoDoToken.limpar()
    armazenamentoDoUsuario.limpar()
  }

  async function entrar(credenciais: CredenciaisDeLogin): Promise<void> {
    iniciarSessao(await api.entrar(credenciais))
  }

  async function registrar(dados: DadosDeRegistro): Promise<void> {
    iniciarSessao(await api.registrar(dados))
  }

  async function sair(): Promise<void> {
    try {
      await api.sair()
    } catch {
      // Se a API não respondeu (ou o token já era inválido), a sessão local sai do mesmo jeito
    }
    limparSessao()
  }

  return { usuario, autenticado, entrar, registrar, sair, limparSessao }
})