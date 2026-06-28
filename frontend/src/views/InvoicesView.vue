<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import api from '../api'
import { eur, formatDate } from '../lib/format'

const router = useRouter()
const invoices = ref<any[]>([])

const TYPE_LABEL: Record<string, string> = { invoice: 'Rechnung', credit_note: 'Gutschrift', cancellation: 'Storno' }

async function load() {
  const { data } = await api.get('/api/invoices')
  invoices.value = data
}
onMounted(load)
</script>

<template>
  <AppShell>
    <template #breadcrumb>Rechnungen</template>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">Rechnungen</h1>
      <button class="btn-primary" @click="router.push('/rechnungen/neu')">+ Neue Rechnung</button>
    </div>

    <div class="card overflow-hidden">
      <div class="table-wrap">
      <table class="w-full">
        <thead class="border-b border-line bg-sand-50/60">
          <tr><th class="th">Nr.</th><th class="th">Typ</th><th class="th">Kunde</th><th class="th">Datum</th><th class="th">Status</th><th class="th text-right">Brutto</th><th class="th text-right">Offen</th></tr>
        </thead>
        <tbody>
          <tr v-for="i in invoices" :key="i.id" class="cursor-pointer border-b border-line last:border-0 hover:bg-sand-50/40" @click="router.push(`/rechnungen/${i.id}`)">
            <td class="td font-medium" :class="i.status === 'cancelled' ? 'text-ink-soft line-through' : ''">{{ i.number ?? '—' }}</td>
            <td class="td text-ink-soft">{{ TYPE_LABEL[i.type] }}</td>
            <td class="td">{{ i.recipientName }}</td>
            <td class="td text-ink-soft">{{ formatDate(i.issueDate) }}</td>
            <td class="td"><StatusBadge :status="i.status" kind="invoice" /></td>
            <td class="td text-right tabular-nums">{{ eur(i.totalGross, i.currency) }}</td>
            <td class="td text-right tabular-nums">{{ i.openAmount > 0 ? eur(i.openAmount, i.currency) : '—' }}</td>
          </tr>
          <tr v-if="!invoices.length"><td class="td text-ink-soft" colspan="7">Noch keine Rechnungen.</td></tr>
        </tbody>
      </table>
      </div>
    </div>
  </AppShell>
</template>
