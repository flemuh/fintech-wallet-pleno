<script setup lang="ts">
import { FileText, House, LogOut, Wallet } from 'lucide-vue-next'
import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useWalletStore } from '@/stores/wallet'

const auth = useAuthStore()
const wallet = useWalletStore()
const roteador = useRouter()
const toast = useToast()

async function sair() {
  await auth.sair()
  wallet.limpar()
  await roteador.push({ name: 'login' })
  toast.info('Você saiu da conta.')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <nav class="flex items-center gap-6">
          <span class="flex items-center gap-2 font-semibold text-emerald-600">
            <Wallet class="size-5" aria-hidden="true" />
            Fintech Wallet
          </span>
          <RouterLink
            :to="{ name: 'home' }"
            class="flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            active-class="font-medium text-slate-900"
          >
            <House class="size-4" aria-hidden="true" />
            Início
          </RouterLink>
          <RouterLink
            :to="{ name: 'transactions' }"
            class="flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900"
            active-class="font-medium text-slate-900"
          >
            <FileText class="size-4" aria-hidden="true" />
            Extrato
          </RouterLink>
        </nav>
        <div class="flex items-center gap-4 text-sm">
          <span class="text-slate-600">{{ auth.usuario?.name }}</span>
          <button
            type="button"
            class="flex items-center gap-1.5 rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-100"
            @click="sair"
          >
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
