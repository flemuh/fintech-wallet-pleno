<script setup lang="ts">
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import AppLogo from '@/components/ui/AppLogo.vue'
import PasswordStrength from '@/components/ui/PasswordStrength.vue'
import { useCampos } from '@/composables/useCampos'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { LIMITES, emailValido, senhaCabeNoLimite, senhaForte } from '@/utils/validacao'

const roteador = useRouter()
const auth = useAuthStore()
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const formulario = reactive({ name: '', email: '', password: '' })

// Mesmas regras do RegisterRequest: nada inválido chega a ser enviado
const campos = useCampos(
  formulario,
  {
    name: () => (formulario.name.trim() ? null : 'Informe o seu nome.'),
    email: () =>
      emailValido(formulario.email) ? null : 'Informe um e-mail válido, como nome@exemplo.com.',
    password: () => {
      if (!senhaCabeNoLimite(formulario.password)) {
        return 'O campo senha ficou longo demais. Use menos acentos ou caracteres especiais.'
      }

      return senhaForte(formulario.password)
        ? null
        : 'A senha ainda não cumpre todos os requisitos abaixo.'
    },
  },
  errosDosCampos,
)

async function registrar(): Promise<void> {
  if (!campos.validarTudo()) return

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
        :maxlength="LIMITES.nome"
        :erro="campos.erroDo('name')"
        :valido="campos.valido('name')"
        @sair="campos.sair('name')"
      />
      <AppField
        id="email"
        v-model="formulario.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        :maxlength="LIMITES.email"
        :erro="campos.erroDo('email')"
        :valido="campos.valido('email')"
        @sair="campos.sair('email')"
      />
      <AppField
        id="password"
        v-model="formulario.password"
        label="Senha"
        type="password"
        autocomplete="new-password"
        :maxlength="LIMITES.senha"
        :erro="campos.erroDo('password')"
        :valido="campos.valido('password')"
        @sair="campos.sair('password')"
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
