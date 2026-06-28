<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import BarChart from '../components/BarChart.vue'
import DonutChart from '../components/DonutChart.vue'
import api from '../api'
import { eur, formatDate } from '../lib/format'

const router = useRouter()
const MONTHS = ['Jän', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']
const QUOTE_STATUS = [
  { key: 'draft', label: 'Entwurf', color: '#9ca3af' },
  { key: 'sent', label: 'Versendet', color: '#2c5a82' },
  { key: 'accepted', label: 'Angenommen', color: '#1f7a4d' },
  { key: 'declined', label: 'Abgelehnt', color: '#b23129' },
  { key: 'expired', label: 'Abgelaufen', color: '#8a5a00' },
]

const kpis = ref({ openCount: 0, openAmount: 0, revenueYtd: 0, customers: 0, quotesOpen: 0 })
const revenueByMonth = ref<{ label: string; value: number }[]>([])
const quoteSegments = ref<{ label: string; value: number; color: string }[]>([])
const recent = ref<any[]>([])
const loaded = ref(false)

onMounted(async () => {
  const [q, inv, cust] = await Promise.all([
    api.get('/api/quotes'),
    api.get('/api/invoices'),
    api.get('/api/customers'),
  ])
  const quotes = q.data
  const invoices = inv.data
  const realInvoices = invoices.filter((i: any) => i.type === 'invoice')
  const finalized = realInvoices.filter((i: any) => !['draft', 'cancelled'].includes(i.status))
  const year = new Date().getFullYear()

  const open = realInvoices.filter((i: any) => ['sent', 'partially_paid', 'overdue'].includes(i.status))
  kpis.value = {
    openCount: open.length,
    openAmount: open.reduce((s: number, i: any) => s + i.openAmount, 0),
    revenueYtd: finalized
      .filter((i: any) => new Date(i.issueDate).getFullYear() === year)
      .reduce((s: number, i: any) => s + i.totalGross, 0),
    customers: cust.data.length,
    quotesOpen: quotes.filter((x: any) => ['draft', 'sent'].includes(x.status)).length,
  }

  // last 12 months
  const now = new Date()
  const buckets: { label: string; value: number }[] = []
  const idx: Record<string, number> = {}
  for (let k = 11; k >= 0; k--) {
    const d = new Date(now.getFullYear(), now.getMonth() - k, 1)
    idx[`${d.getFullYear()}-${d.getMonth()}`] = buckets.length
    buckets.push({ label: MONTHS[d.getMonth()], value: 0 })
  }
  finalized.forEach((i: any) => {
    const d = new Date(i.issueDate)
    const key = `${d.getFullYear()}-${d.getMonth()}`
    if (key in idx) buckets[idx[key]].value += i.totalGross
  })
  revenueByMonth.value = buckets

  quoteSegments.value = QUOTE_STATUS.map((s) => ({
    label: s.label,
    color: s.color,
    value: quotes.filter((x: any) => x.status === s.key).length,
  }))

  recent.value = [
    ...quotes.map((x: any) => ({ kind: 'Angebot', route: `/angebote/${x.id}`, number: x.number, name: x.recipientName, date: x.createdAt, gross: x.totalGross, status: x.status })),
    ...invoices.map((x: any) => ({ kind: 'Rechnung', route: `/rechnungen/${x.id}`, number: x.number, name: x.recipientName, date: x.createdAt, gross: x.totalGross, status: x.status })),
  ]
    .sort((a, b) => b.date.localeCompare(a.date))
    .slice(0, 6)

  loaded.value = true
})
</script>

<template>
  <AppShell>
    <template #breadcrumb>Dashboard</template>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-ink">Übersicht</h1>

    <!-- KPIs -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Umsatz {{ new Date().getFullYear() }}</div>
        <div class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ eur(kpis.revenueYtd) }}</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Offener Betrag</div>
        <div class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ eur(kpis.openAmount) }}</div>
        <div class="mt-0.5 text-xs text-ink-soft">{{ kpis.openCount }} offene Rechnung(en)</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Offene Angebote</div>
        <div class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ kpis.quotesOpen }}</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Kunden</div>
        <div class="mt-1 text-2xl font-semibold tabular-nums text-ink">{{ kpis.customers }}</div>
      </div>
    </div>

    <!-- charts -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="card p-6 lg:col-span-2">
        <h2 class="mb-1 text-base font-semibold text-ink">Umsatz pro Monat</h2>
        <p class="mb-5 text-xs text-ink-soft">Rechnungen der letzten 12 Monate (brutto)</p>
        <BarChart v-if="loaded" :data="revenueByMonth" />
      </div>
      <div class="card p-6">
        <h2 class="mb-1 text-base font-semibold text-ink">Angebote nach Status</h2>
        <p class="mb-5 text-xs text-ink-soft">Verteilung & Conversion</p>
        <DonutChart v-if="loaded" :segments="quoteSegments" />
      </div>
    </div>

    <!-- recent -->
    <div class="mt-6 card overflow-hidden">
      <div class="border-b border-line px-6 py-4">
        <h2 class="text-base font-semibold text-ink">Letzte Belege</h2>
      </div>
      <table class="w-full">
        <thead class="border-b border-line bg-sand-50/60">
          <tr><th class="th">Typ</th><th class="th">Nr.</th><th class="th">Kunde</th><th class="th">Datum</th><th class="th">Status</th><th class="th text-right">Betrag</th></tr>
        </thead>
        <tbody>
          <tr v-for="(d, i) in recent" :key="i" class="cursor-pointer border-b border-line last:border-0 hover:bg-sand-50/40" @click="router.push(d.route)">
            <td class="td text-ink-soft">{{ d.kind }}</td>
            <td class="td font-medium">{{ d.number ?? '—' }}</td>
            <td class="td">{{ d.name }}</td>
            <td class="td text-ink-soft">{{ formatDate(d.date) }}</td>
            <td class="td"><StatusBadge :status="d.status" /></td>
            <td class="td text-right tabular-nums">{{ eur(d.gross) }}</td>
          </tr>
          <tr v-if="loaded && !recent.length"><td class="td text-ink-soft" colspan="6">Noch keine Belege.</td></tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>
