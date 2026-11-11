<script setup lang="ts">
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
      class="w-full max-w-sm space-y-4 rounded-lg bg-white p-6 shadow"
      novalidate
      @submit.prevent="registrar"
    >
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
        class="w-full rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ enviando ? 'Criando conta...' : 'Criar conta' }}
      </button>

      <p class="text-center text-sm text-slate-600">
        Já tem conta?
        <RouterLink :to="{ name: 'login' }" class="text-indigo-600 hover:underline">
          Entrar
        </RouterLink>
      </p>
    </form>
  </main>
</template>