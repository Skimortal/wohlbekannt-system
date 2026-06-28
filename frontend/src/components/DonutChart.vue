<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ segments: { label: string; value: number; color: string }[] }>()

const R = 60
const STROKE = 22
const C = 2 * Math.PI * R

const total = computed(() => props.segments.reduce((s, x) => s + x.value, 0))

const arcs = computed(() => {
  let acc = 0
  return props.segments
    .filter((s) => s.value > 0)
    .map((s) => {
      const frac = total.value ? s.value / total.value : 0
      const dash = frac * C
      const arc = { ...s, dash, gap: C - dash, offset: -acc * C }
      acc += frac
      return arc
    })
})
</script>

<template>
  <div class="flex items-center gap-6">
    <div class="relative shrink-0">
      <svg width="160" height="160" viewBox="0 0 160 160">
        <g transform="rotate(-90 80 80)">
          <circle cx="80" cy="80" :r="R" fill="none" stroke="var(--color-line)" :stroke-width="STROKE" />
          <circle
            v-for="(a, i) in arcs"
            :key="i"
            cx="80"
            cy="80"
            :r="R"
            fill="none"
            :stroke="a.color"
            :stroke-width="STROKE"
            :stroke-dasharray="`${a.dash} ${a.gap}`"
            :stroke-dashoffset="a.offset"
          />
        </g>
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-2xl font-semibold text-ink">{{ total }}</span>
        <span class="text-xs text-ink-soft">gesamt</span>
      </div>
    </div>
    <ul class="space-y-1.5 text-sm">
      <li v-for="(s, i) in segments" :key="i" class="flex items-center gap-2">
        <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ background: s.color }"></span>
        <span class="text-ink-soft">{{ s.label }}</span>
        <span class="ml-auto font-medium tabular-nums text-ink">{{ s.value }}</span>
      </li>
    </ul>
  </div>
</template>
