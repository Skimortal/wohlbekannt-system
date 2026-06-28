<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: string; kind?: 'quote' | 'invoice' }>()

const MAP: Record<string, { label: string; cls: string }> = {
  // Angebot
  draft: { label: 'Entwurf', cls: 'bg-gray-100 text-gray-600 ring-gray-200' },
  sent: { label: 'Versendet', cls: 'bg-blue-50 text-blue-700 ring-blue-200' },
  accepted: { label: 'Angenommen', cls: 'bg-green-50 text-green-700 ring-green-200' },
  declined: { label: 'Abgelehnt', cls: 'bg-red-50 text-red-700 ring-red-200' },
  expired: { label: 'Abgelaufen', cls: 'bg-amber-50 text-amber-700 ring-amber-200' },
  // Rechnung
  partially_paid: { label: 'Teilbezahlt', cls: 'bg-amber-50 text-amber-700 ring-amber-200' },
  paid: { label: 'Bezahlt', cls: 'bg-green-50 text-green-700 ring-green-200' },
  overdue: { label: 'Überfällig', cls: 'bg-red-50 text-red-700 ring-red-200' },
  cancelled: { label: 'Storniert', cls: 'bg-gray-100 text-gray-400 ring-gray-200' },
}

const info = computed(() => MAP[props.status] ?? { label: props.status, cls: 'bg-gray-100 text-gray-600 ring-gray-200' })
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
    :class="info.cls"
  >
    {{ info.label }}
  </span>
</template>
