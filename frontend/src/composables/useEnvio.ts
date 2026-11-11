import { ref } from 'vue'

import { lerErroDaApi } from '@/api/http'

export function useEnvio() {
  const enviando = ref(false)
  const erroGeral = ref<string | null>(null)
  const errosDosCampos = ref<Record<string, string[]>>({})

  /** Executa o envio e devolve se deu certo. Os erros ficam nas refs para a tela exibir. */
  async function enviar(acao: () => Promise<unknown>): Promise<boolean> {
    enviando.value = true
    erroGeral.value = null
    errosDosCampos.value = {}

    try {
      await acao()
      return true
    } catch (erro) {
      const { message, code, errors } = lerErroDaApi(erro)
      errosDosCampos.value = errors
      // Validação aparece em cada campo; qualquer outro erro vira mensagem geral
      erroGeral.value = code === 'validation_failed' ? null : message
      return false
    } finally {
      enviando.value = false
    }
  }

  return { enviando, erroGeral, errosDosCampos, enviar }
}