<script setup lang="ts">
defineProps<{ tipo: 'erro' | 'sucesso' }>()
</script>

<template>
  <p
    :role="tipo === 'erro' ? 'alert' : 'status'"
    class="rounded p-3 text-sm"
    :class="tipo === 'erro' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'"
  >
    <slot />
  </p>
</template>