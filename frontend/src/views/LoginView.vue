<script setup lang="ts">
import { reactive, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AppAlert from '@/components/ui/AppAlert.vue'
import AppField from '@/components/ui/AppField.vue'
import AppLogo from '@/components/ui/AppLogo.vue'
import { useCampos } from '@/composables/useCampos'
import { useEnvio } from '@/composables/useEnvio'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { LIMITES, emailValido } from '@/utils/validacao'

const roteador = useRouter()
const auth = useAuthStore()
const toast = useToast()
const { enviando, erroGeral, errosDosCampos, enviar } = useEnvio()

const formulario = reactive({ email: '', password: '' })

// O login não repete as regras de senha do cadastro: quem diz se ela está certa é a API,
// sempre com a mesma resposta genérica
const campos = useCampos(
  formulario,
  {
    email: () =>
      emailValido(formulario.email) ? null : 'Informe um e-mail válido, como nome@exemplo.com.',
    password: () => (formulario.password ? null : 'Informe a senha.'),
  },
  errosDosCampos,
)

// "Credenciais inválidas." vale para os dois campos: some quando qualquer um deles muda
watch(formulario, () => {
  errosDosCampos.value = {}
})

async function entrar(): Promise<void> {
  if (!campos.validarTudo()) return

  const sucesso = await enviar(() => auth.entrar(formulario))
  if (!sucesso) return

  await roteador.push({ name: 'home' })
  toast.sucesso(`Bem-vindo, ${auth.usuario?.name ?? ''}.`)
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <form class="cartao w-full max-w-sm space-y-4 p-6" novalidate @submit.prevent="entrar">
      <AppLogo />
      <h1 class="text-xl font-semibold text-slate-900">Entrar</h1>

      <AppAlert v-if="erroGeral" tipo="erro">{{ erroGeral }}</AppAlert>

      <AppField
        id="email"
        v-model="formulario.email"
        label="E-mail"
        type="email"
        autocomplete="email"
        :maxlength="LIMITES.email"
        :erro="campos.erroDo('email')"
        @sair="campos.sair('email')"
      />
      <AppField
        id="password"
        v-model="formulario.password"
        label="Senha"
        type="password"
        autocomplete="current-password"
        :maxlength="LIMITES.senha"
        :erro="campos.erroDo('password')"
        @sair="campos.sair('password')"
      />

      <button type="submit" :disabled="enviando" class="botao-primario w-full">
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
