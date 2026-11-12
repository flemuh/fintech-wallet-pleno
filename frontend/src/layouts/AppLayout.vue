<script setup lang="ts">
import { FileText, House, LogOut, Menu, Wallet, X } from '@lucide/vue'
import { computed, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'

import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { useWalletStore } from '@/stores/wallet'

const auth = useAuthStore()
const wallet = useWalletStore()
const rota = useRoute()
const roteador = useRouter()
const toast = useToast()

const menuAberto = ref(false)

// Ao navegar pelo menu do celular, ele fecha sozinho
watch(
  () => rota.fullPath,
  () => {
    menuAberto.value = false
  },
)

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
  menuAberto.value = false
  await auth.sair()
  wallet.limpar()
  await roteador.push({ name: 'login' })
  toast.info('Você saiu da conta.')
}

const classeDoLink =
  'flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-violet-50 hover:text-violet-800'
</script>

<template>
  <div class="min-h-screen overflow-x-hidden bg-slate-50">
    <header class="border-b border-violet-100 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
        <span class="flex items-center gap-2 font-semibold whitespace-nowrap text-violet-800">
          <span class="flex size-8 items-center justify-center rounded-lg bg-violet-700 text-white">
            <Wallet class="size-4" aria-hidden="true" />
          </span>
          Fintech Wallet
        </span>

        <!-- Telas médias e grandes: tudo na mesma linha -->
        <nav class="hidden flex-1 items-center gap-1 md:flex" aria-label="Principal">
          <RouterLink
            :to="{ name: 'home' }"
            :class="classeDoLink"
            exact-active-class="bg-violet-50 font-medium text-violet-800"
          >
            <House class="size-4" aria-hidden="true" />
            Início
          </RouterLink>
          <RouterLink
            :to="{ name: 'transactions' }"
            :class="classeDoLink"
            active-class="bg-violet-50 font-medium text-violet-800"
          >
            <FileText class="size-4" aria-hidden="true" />
            Extrato
          </RouterLink>
        </nav>
        <div class="hidden items-center gap-3 text-sm text-slate-600 md:flex">
          <span
            class="flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-800"
            aria-hidden="true"
          >
            {{ iniciais }}
          </span>
          <span class="max-w-40 truncate">{{ auth.usuario?.name }}</span>
          <button type="button" class="botao-secundario px-3 py-1.5" @click="sair">
            <LogOut class="size-4" aria-hidden="true" />
            Sair
          </button>
        </div>

        <!-- Celular: só a marca e o botão do menu -->
        <button
          type="button"
          class="botao-secundario size-10 shrink-0 p-0 md:hidden"
          :aria-label="menuAberto ? 'Fechar menu' : 'Abrir menu'"
          :aria-expanded="menuAberto"
          aria-controls="menu-mobile"
          @click="menuAberto = !menuAberto"
        >
          <X v-if="menuAberto" class="size-5" aria-hidden="true" />
          <Menu v-else class="size-5" aria-hidden="true" />
        </button>
      </div>

      <nav
        v-if="menuAberto"
        id="menu-mobile"
        class="border-t border-violet-100 px-4 pt-2 pb-4 md:hidden"
        aria-label="Menu"
      >
        <div class="flex items-center gap-3 px-3 py-3">
          <span
            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-800"
            aria-hidden="true"
          >
            {{ iniciais }}
          </span>
          <span class="min-w-0 truncate text-sm font-medium text-slate-800">
            {{ auth.usuario?.name }}
          </span>
        </div>
        <RouterLink
          :to="{ name: 'home' }"
          :class="classeDoLink"
          exact-active-class="bg-violet-50 font-medium text-violet-800"
        >
          <House class="size-5" aria-hidden="true" />
          Início
        </RouterLink>
        <RouterLink
          :to="{ name: 'transactions' }"
          :class="classeDoLink"
          active-class="bg-violet-50 font-medium text-violet-800"
        >
          <FileText class="size-5" aria-hidden="true" />
          Extrato
        </RouterLink>
        <button type="button" class="botao-secundario mt-3 w-full" @click="sair">
          <LogOut class="size-4" aria-hidden="true" />
          Sair
        </button>
      </nav>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6 sm:py-8">
      <RouterView />
    </main>
  </div>
</template>
