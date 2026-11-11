<script setup lang="ts">
import { Wallet } from '@lucide/vue'
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import { useEnvio } from '@/composables/useEnvio'
import { useAuthStore } from '@/stores/auth'

const roteador = useRouter()
const auth = useAuthStore()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const formulario = reactive({ name: '', email: '', password: '' })

async function registrar(): Promise<void> {
  const sucesso = await enviar(() => auth.registrar(formulario))
  if (sucesso) await roteador.push({ name: 'home' })
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <form
      class="cartao w-full max-w-sm space-y-4 p-6"
      novalidate
      @submit.prevent="registrar"
    >
      <p class="flex items-center gap-2 font-semibold text-violet-800"><span class="flex size-8 items-center justify-center rounded-lg bg-violet-700 text-white"><Wallet class="size-4" aria-hidden="true" /></span>Fintech Wallet</p>
      <h1 class="text-xl font-semibold text-slate-900">Criar conta</h1>

      <AppAlert v-if="erroGeral" tipo="erro">{{ erroGeral }}</AppAlert>

      <AppField
        id="name"
        v-model="formulario.name"
        label="Nome"
        autocomplete="name"
        :erro="errosDosCampos.name?.[0]"
      />
      <AppField
        id="email"
        v-model="formulario.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        :erro="errosDosCampos.email?.[0]"
      />
      <AppField
        id="password"
        v-model="formulario.password"
        label="Senha"
        type="password"
        autocomplete="new-password"
        dica="Mínimo de 8 caracteres, com letras e números."
        :erro="errosDosCampos.password?.[0]"
      />

      <button
        type="submit"
        :disabled="enviando"
        class="botao-primario w-full"
      >
        {{ enviando ? 'Criando conta...' : 'Criar conta' }}
      </button>

      <p class="text-center text-sm text-slate-600">
        Já tem conta?
        <RouterLink :to="{ name: 'login' }" class="font-medium text-violet-700 hover:underline">
          Entrar
        </RouterLink>
      </p>
    </form>
  </main>
</template>