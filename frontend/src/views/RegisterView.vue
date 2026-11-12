<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import AppLogo from '@/components/ui/AppLogo.vue'
import PasswordStrength from '@/components/ui/PasswordStrength.vue'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { emailValido, senhaForte } from '@/utils/validacao'

type Campo = 'name' | 'email' | 'password'

const roteador = useRouter()
const auth = useAuthStore()
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const formulario = reactive({ name: '', email: '', password: '' })
const errosLocais = ref<Partial<Record<Campo, string>>>({})

// O erro da tela vem antes do da API: nada inválido chega a ser enviado
function erroDo(campo: Campo): string | undefined {
  return errosLocais.value[campo] ?? errosDosCampos.value[campo]?.[0]
}

function validarNaTela(): boolean {
  const erros: Partial<Record<Campo, string>> = {}

  if (!formulario.name.trim()) erros.name = 'Informe o seu nome.'
  if (!emailValido(formulario.email))
    erros.email = 'Informe um e-mail válido, como nome@exemplo.com.'
  if (!senhaForte(formulario.password))
    erros.password = 'A senha ainda não cumpre todos os requisitos abaixo.'

  errosLocais.value = erros
  return Object.keys(erros).length === 0
}

async function registrar(): Promise<void> {
  if (!validarNaTela()) return

  const sucesso = await enviar(() => auth.registrar(formulario))
  if (!sucesso) return

  await roteador.push({ name: 'home' })
  toast.sucesso(`Conta criada. Bem-vindo, ${auth.usuario?.name ?? ''}.`)
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <form class="cartao w-full max-w-sm space-y-4 p-6" novalidate @submit.prevent="registrar">
      <AppLogo />
      <h1 class="text-xl font-semibold text-slate-900">Criar conta</h1>

      <AppAlert v-if="erroGeral" tipo="erro">{{ erroGeral }}</AppAlert>

      <AppField
        id="name"
        v-model="formulario.name"
        label="Nome"
        autocomplete="name"
        :erro="erroDo('name')"
      />
      <AppField
        id="email"
        v-model="formulario.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        :erro="erroDo('email')"
      />
      <AppField
        id="password"
        v-model="formulario.password"
        label="Senha"
        type="password"
        autocomplete="new-password"
        :erro="erroDo('password')"
      />
      <PasswordStrength :senha="formulario.password" />

      <button type="submit" :disabled="enviando" class="botao-primario w-full">
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
