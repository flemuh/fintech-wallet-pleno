<script setup lang="ts">
defineProps<{
  id: string
  label: string
  type?: string
  autocomplete?: string
  dica?: string
  erro?: string
}>()

const valor = defineModel<string>({ required: true })
</script>

<template>
  <div>
    <label :for="id" class="block text-sm font-medium text-slate-700">{{ label }}</label>
    <input
      :id="id"
      v-model="valor"
      :type="type ?? 'text'"
      :autocomplete="autocomplete"
      :aria-invalid="erro ? 'true' : undefined"
      class="mt-1 block w-full rounded border px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
      :class="erro ? 'border-red-500' : 'border-slate-300'"
    />
    <p v-if="erro" class="mt-1 text-sm text-red-600">{{ erro }}</p>
    <p v-else-if="dica" class="mt-1 text-sm text-slate-500">{{ dica }}</p>
  </div>
</template>
