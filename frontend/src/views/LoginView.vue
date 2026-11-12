<script setup lang="ts">
import { Wallet } from '@lucide/vue'
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { emailValido, senhaDeLoginValida } from '@/utils/validacao'

const roteador = useRouter()
const auth = useAuthStore()
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const formulario = reactive({ email: '', password: '' })
const erroDoEmail = ref<string | null>(null)
const erroDaSenha = ref<string | null>(null)

async function entrar(): Promise<void> {
  erroDoEmail.value = emailValido(formulario.email) ? null : 'Informe um e-mail válido, como nome@exemplo.com.'
  erroDaSenha.value = senhaDeLoginValida(formulario.password)
    ? null
    : 'A senha deve ter pelo menos 8 caracteres.'
  if (erroDoEmail.value || erroDaSenha.value) return

  const sucesso = await enviar(() => auth.entrar(formulario))
  if (!sucesso) return

  await roteador.push({ name: 'home' })
  toast.sucesso(`Bem-vindo, ${auth.usuario?.name ?? ''}.`)
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <form
      class="cartao w-full max-w-sm space-y-4 p-6"
      novalidate
      @submit.prevent="entrar"
    >
      <p class="flex items-center gap-2 font-semibold text-violet-800"><span class="flex size-8 items-center justify-center rounded-lg bg-violet-700 text-white"><Wallet class="size-4" aria-hidden="true" /></span>Fintech Wallet</p>
      <h1 class="text-xl font-semibold text-slate-900">Entrar</h1>

      <AppAlert v-if="erroGeral" tipo="erro">{{ erroGeral }}</AppAlert>

      <AppField
        id="email"
        v-model="formulario.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        :erro="erroDoEmail ?? errosDosCampos.email?.[0]"
      />
      <AppField
        id="password"
        v-model="formulario.password"
        label="Senha"
        type="password"
        autocomplete="current-password"
        :erro="erroDaSenha ?? errosDosCampos.password?.[0]"
      />

      <button
        type="submit"
        :disabled="enviando"
        class="botao-primario w-full"
      >
        {{ enviando ? 'Entrando...' : 'Entrar' }}
      </button>

      <p class="text-center text-sm text-slate-600">
        Não tem conta?
        <RouterLink :to="{ name: 'register' }" class="font-medium text-violet-700 hover:underline">
          Criar conta
        </RouterLink>
      </p>
    </form>
  </main>
</template>
