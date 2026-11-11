<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { getHealth } from '@/api/health'

const apiStatus = ref('verificando...')

onMounted(async () => {
  try {
    const { status } = await getHealth()
    apiStatus.value = status
  } catch {
    apiStatus.value = 'indisponível'
  }
})
</script>

<template>
  <section>
    <h1 class="text-3xl font-bold text-emerald-600">Fintech Wallet</h1>
    <p class="mt-2 text-gray-600">API: {{ apiStatus }}</p>
  </section>
</template>