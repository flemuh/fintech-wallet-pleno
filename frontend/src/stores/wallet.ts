import { ref } from 'vue'
import { defineStore } from 'pinia'

import {
  buscarResumo,
  depositar as depositarNaApi,
  listarTransacoes,
  sacar as sacarNaApi,
} from '@/api/wallet'
import type { ResumoDaCarteira, Transacao } from '@/api/wallet'
import { lerErroDaApi } from '@/api/http'

const TRANSACOES_NO_PAINEL = 5

export const useWalletStore = defineStore('wallet', () => {
  const resumo = ref<ResumoDaCarteira | null>(null)
  const recentes = ref<Transacao[]>([])
  const carregando = ref(false)
  const erro = ref<string | null>(null)

  async function carregarPainel(): Promise<void> {
    carregando.value = true
    erro.value = null

    try {
      const [novoResumo, pagina] = await Promise.all([
        buscarResumo(),
        listarTransacoes({ per_page: TRANSACOES_NO_PAINEL }),
      ])
      resumo.value = novoResumo
      recentes.value = pagina.data
    } catch (falha) {
      erro.value = lerErroDaApi(falha).message
    } finally {
      carregando.value = false
    }
  }

  // Se a operação falhar, o erro sobe para o formulário; se der certo, o painel já mostra o novo saldo
  async function depositar(valorEmReais: number, chave: string): Promise<void> {
    await depositarNaApi(valorEmReais, chave)
    await carregarPainel()
  }

  async function sacar(valorEmReais: number, chave: string): Promise<void> {
    await sacarNaApi(valorEmReais, chave)
    await carregarPainel()
  }

  // Chamado ao sair: o próximo usuário não pode ver os dados do anterior
  function limpar(): void {
    resumo.value = null
    recentes.value = []
    erro.value = null
  }

  return { resumo, recentes, carregando, erro, carregarPainel, depositar, sacar, limpar }
})
