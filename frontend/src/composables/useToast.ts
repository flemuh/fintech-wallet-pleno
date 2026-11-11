import { readonly, ref } from 'vue'

export type TipoDeToast = 'sucesso' | 'erro' | 'info' | 'alerta'

export interface Toast {
  id: number
  tipo: TipoDeToast
  mensagem: string
}

// Erros ficam mais tempo na tela, porque dão mais trabalho de ler
const DURACAO_EM_MS: Record<TipoDeToast, number> = {
  sucesso: 4000,
  info: 4000,
  alerta: 5000,
  erro: 6000,
}

// O estado é do módulo, não de um componente: qualquer tela ou o main.ts pode disparar um toast
const toasts = ref<Toast[]>([])
let proximoId = 1

function remover(id: number): void {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

function mostrar(tipo: TipoDeToast, mensagem: string): void {
  const id = proximoId++

  toasts.value = [...toasts.value, { id, tipo, mensagem }]
  setTimeout(() => remover(id), DURACAO_EM_MS[tipo])
}

export function useToast() {
  return {
    toasts: readonly(toasts),
    remover,
    limpar: (): void => {
      toasts.value = []
    },
    sucesso: (mensagem: string): void => mostrar('sucesso', mensagem),
    erro: (mensagem: string): void => mostrar('erro', mensagem),
    info: (mensagem: string): void => mostrar('info', mensagem),
    alerta: (mensagem: string): void => mostrar('alerta', mensagem),
  }
}
