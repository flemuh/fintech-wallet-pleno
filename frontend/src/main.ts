import '@/assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from '@/App.vue'
import { registrarAoExpirarSessao } from '@/api/http'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Um 401 com token salvo significa sessão expirada: limpa tudo e volta ao login.
registrarAoExpirarSessao(() => {
  useAuthStore(pinia).limparSessao()
  void router.push({ name: 'login' })
})

app.mount('#app')