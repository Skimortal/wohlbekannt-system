<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ page: number; limit: number; total: number }>()
const emit = defineEmits<{ go: [page: number] }>()

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.limit)))
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.limit + 1))
const to = computed(() => Math.min(props.page * props.limit, props.total))

const pages = computed<(number | string)[]>(() => {
  const tp = totalPages.value
  if (tp <= 7) return Array.from({ length: tp }, (_, i) => i + 1)
  const p = props.page
  const out: (number | string)[] = [1]
  const start = Math.max(2, p - 1)
  const end = Math.min(tp - 1, p + 1)
  if (start > 2) out.push('…')
  for (let i = start; i <= end; i++) out.push(i)
  if (end < tp - 1) out.push('…')
  out.push(tp)
  return out
})
</script>

<template>
  <div v-if="total > 0" class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
    <span class="text-sm text-ink-soft">Zeige {{ from }}–{{ to }} von {{ total }}</span>
    <div v-if="totalPages > 1" class="flex items-center gap-1">
      <button class="btn-secondary px-2.5 py-1" :disabled="page <= 1" @click="emit('go', page - 1)">‹</button>
      <template v-for="(pg, i) in pages" :key="i">
        <span v-if="pg === '…'" class="px-2 text-ink-soft">…</span>
        <button
          v-else
          class="min-w-9 rounded-lg px-3 py-1 text-sm transition"
          :class="pg === page ? 'bg-ink text-paper' : 'text-ink hover:bg-sand-50'"
          @click="emit('go', pg as number)"
        >
          {{ pg }}
        </button>
      </template>
      <button class="btn-secondary px-2.5 py-1" :disabled="page >= totalPages" @click="emit('go', page + 1)">›</button>
    </div>
  </div>
</template>
