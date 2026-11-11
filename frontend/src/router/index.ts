import { createRouter, createWebHistory, type RouterHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import DashboardView from '@/views/DashboardView.vue'

declare module 'vue-router' {
  interface RouteMeta {
    requerAutenticacao?: boolean
    somenteVisitante?: boolean
  }
}

export function criarRoteador(historico: RouterHistory) {
  const roteador = createRouter({
    history: historico,
    routes: [
      {
        path: '/',
        component: AppLayout,
        meta: { requerAutenticacao: true },
        children: [{ path: '', name: 'home', component: DashboardView }],
      },
      {
        path: '/login',
        name: 'login',
        component: () => import('@/views/LoginView.vue'),
        meta: { somenteVisitante: true },
      },
      {
        path: '/register',
        name: 'register',
        component: () => import('@/views/RegisterView.vue'),
        meta: { somenteVisitante: true },
      },
    ],
  })

  roteador.beforeEach((destino) => {
    const auth = useAuthStore()

    if (destino.meta.requerAutenticacao && !auth.autenticado) {
      return { name: 'login' }
    }

    if (destino.meta.somenteVisitante && auth.autenticado) {
      return { name: 'home' }
    }
  })

  return roteador
}

export default criarRoteador(createWebHistory(import.meta.env.BASE_URL))