<script setup lang="ts">
import { eur } from '../lib/format'
import type { DocumentDiscount, Totals } from '../lib/totals'

defineProps<{
  totals: Totals
  currency: string
  pricesIncludeVat: boolean
  editable: boolean
}>()
const discount = defineModel<DocumentDiscount>('discount', { required: true })
</script>

<template>
  <div class="card ml-auto max-w-md p-6">
    <div v-if="editable" class="mb-4 border-b border-line pb-4">
      <label class="label">Gesamtrabatt</label>
      <div class="flex gap-2">
        <select v-model="discount.type" class="input !w-auto">
          <option value="">kein Rabatt</option>
          <option value="percent">in %</option>
          <option value="amount">als Betrag ({{ currency }} {{ pricesIncludeVat ? 'brutto' : 'netto' }})</option>
        </select>
        <input
          v-if="discount.type"
          v-model="discount.value"
          class="input text-right tabular-nums"
          :placeholder="discount.type === 'percent' ? 'z.B. 10' : 'z.B. 50,00'"
        />
      </div>
    </div>

    <div class="space-y-1.5 text-sm">
      <template v-if="totals.discount !== 0">
        <div class="flex justify-between">
          <span class="text-ink-soft">Zwischensumme {{ pricesIncludeVat ? 'brutto' : 'netto' }}</span>
          <span class="tabular-nums">{{ eur(totals.subtotal, currency) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-ink-soft">Rabatt{{ discount.type === 'percent' ? ` ${discount.value} %` : '' }}</span>
          <span class="tabular-nums">{{ eur(-totals.discount, currency) }}</span>
        </div>
      </template>
      <div class="flex justify-between"><span class="text-ink-soft">Gesamtbetrag netto</span><span class="tabular-nums">{{ eur(totals.net, currency) }}</span></div>
      <div v-for="g in totals.breakdown" :key="g.rate" class="flex justify-between">
        <span class="text-ink-soft">zzgl. USt {{ g.rate }}%</span><span class="tabular-nums">{{ eur(g.tax, currency) }}</span>
      </div>
      <div class="flex justify-between border-t border-ink pt-2 font-semibold"><span>Gesamtbetrag brutto</span><span class="tabular-nums">{{ eur(totals.gross, currency) }}</span></div>
      <slot />
    </div>
  </div>
</template>
