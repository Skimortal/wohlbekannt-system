<script setup lang="ts">
defineProps<{ title?: string }>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/30 p-4 py-10" @click.self="emit('close')">
    <div class="w-full max-w-2xl rounded-2xl border border-line bg-paper shadow-lg">
      <div class="flex items-center justify-between border-b border-line px-6 py-4">
        <h2 class="text-base font-semibold text-ink">{{ title }}</h2>
        <button class="text-ink-soft hover:text-ink" @click="emit('close')">✕</button>
      </div>
      <div class="px-6 py-5">
        <slot />
      </div>
      <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
