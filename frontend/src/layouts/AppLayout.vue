<script setup lang="ts">
import { FileText, House, LogOut, Wallet } from '@lucide/vue'
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useWalletStore } from '@/stores/wallet'

const auth = useAuthStore()
const wallet = useWalletStore()
const roteador = useRouter()
const toast = useToast()

// "Usuário Demo" vira "UD"
const iniciais = computed(() =>
  (auth.usuario?.name ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join(''),
)

async function sair() {
  await auth.sair()
  wallet.limpar()
  await roteador.push({ name: 'login' })
  toast.info('Você saiu da conta.')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="border-b border-violet-100 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <nav class="flex items-center gap-2">
          <span class="mr-4 flex items-center gap-2 font-semibold text-violet-800">
            <span class="flex size-8 items-center justify-center rounded-lg bg-violet-700 text-white">
              <Wallet class="size-4" aria-hidden="true" />
            </span>
            Fintech Wallet
          </span>
          <RouterLink
            :to="{ name: 'home' }"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-slate-600 transition-colors hover:bg-violet-50 hover:text-violet-800"
            exact-active-class="bg-violet-50 font-medium text-violet-800"
          >
            <House class="size-4" aria-hidden="true" />
            Início
          </RouterLink>
          <RouterLink
            :to="{ name: 'transactions' }"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-slate-600 transition-colors hover:bg-violet-50 hover:text-violet-800"
            active-class="bg-violet-50 font-medium text-violet-800"
          >
            <FileText class="size-4" aria-hidden="true" />
            Extrato
          </RouterLink>
        </nav>
        <div class="flex items-center gap-3 text-sm text-slate-600">
          <span
            class="flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-800"
            aria-hidden="true"
          >
            {{ iniciais }}
          </span>
          <span>{{ auth.usuario?.name }}</span>
          <button type="button" class="botao-secundario px-3 py-1.5" @click="sair">
            <LogOut class="size-4" aria-hidden="true" />
            Sair
          </button>
        </div>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>
