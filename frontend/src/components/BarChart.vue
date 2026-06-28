<script setup lang="ts">
import { computed } from 'vue'
import { eur } from '../lib/format'

const props = defineProps<{ data: { label: string; value: number }[] }>()

const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)))
function height(v: number): string {
  return `${Math.max(2, (v / max.value) * 100)}%`
}
</script>

<template>
  <div>
    <div class="flex h-52 items-end gap-2">
      <div v-for="(d, i) in data" :key="i" class="group flex h-full flex-1 flex-col items-center justify-end gap-2">
        <div class="relative flex w-full flex-1 items-end">
          <div
            class="w-full rounded-t-md bg-sand transition-all group-hover:bg-sand-300"
            :style="{ height: height(d.value) }"
            :title="`${d.label}: ${eur(d.value)}`"
          ></div>
          <span
            class="pointer-events-none absolute -top-5 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] tabular-nums text-ink opacity-0 transition group-hover:opacity-100"
          >{{ eur(d.value) }}</span>
        </div>
        <span class="text-xs text-ink-soft">{{ d.label }}</span>
      </div>
    </div>
  </div>
</template>
