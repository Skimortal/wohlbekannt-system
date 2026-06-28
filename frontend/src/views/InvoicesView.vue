<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import api from '../api'
import { eur, formatDate } from '../lib/format'

const router = useRouter()
const invoices = ref<any[]>([])
const search = ref('')
const status = ref('')
const type = ref('')

const TYPE_LABEL: Record<string, string> = { invoice: 'Rechnung', credit_note: 'Gutschrift', cancellation: 'Storno' }
const STATUS = [
  { v: '', l: 'Alle Status' },
  { v: 'draft', l: 'Entwurf' },
  { v: 'sent', l: 'Versendet' },
  { v: 'partially_paid', l: 'Teilbezahlt' },
  { v: 'paid', l: 'Bezahlt' },
  { v: 'overdue', l: 'Überfällig' },
  { v: 'cancelled', l: 'Storniert' },
]
const TYPES = [
  { v: '', l: 'Alle Typen' },
  { v: 'invoice', l: 'Rechnung' },
  { v: 'credit_note', l: 'Gutschrift' },
  { v: 'cancellation', l: 'Storno' },
]

async function load() {
  const { data } = await api.get('/api/invoices', { params: { q: search.value, status: status.value, type: type.value } })
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

    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
      <input v-model="search" class="input sm:max-w-xs" placeholder="Suchen (Nr., Kunde) …" @input="load" />
      <select v-model="status" class="input sm:max-w-[180px]" @change="load">
        <option v-for="s in STATUS" :key="s.v" :value="s.v">{{ s.l }}</option>
      </select>
      <select v-model="type" class="input sm:max-w-[180px]" @change="load">
        <option v-for="t in TYPES" :key="t.v" :value="t.v">{{ t.l }}</option>
      </select>
    </div>

    <!-- desktop table -->
    <div class="hidden card overflow-hidden lg:block">
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
            <tr v-if="!invoices.length"><td class="td text-ink-soft" colspan="7">Keine Rechnungen gefunden.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- mobile cards -->
    <div class="space-y-3 lg:hidden">
      <button
        v-for="i in invoices"
        :key="i.id"
        class="card w-full p-4 text-left"
        @click="router.push(`/rechnungen/${i.id}`)"
      >
        <div class="flex items-center justify-between">
          <span class="font-medium text-ink" :class="i.status === 'cancelled' ? 'text-ink-soft line-through' : ''">{{ i.number ?? 'Entwurf' }}</span>
          <StatusBadge :status="i.status" kind="invoice" />
        </div>
        <div class="mt-1 text-sm text-ink">{{ i.recipientName }}</div>
        <div class="mt-0.5 text-xs text-ink-soft">{{ TYPE_LABEL[i.type] }} · {{ formatDate(i.issueDate) }}</div>
        <div class="mt-2 flex items-center justify-between text-sm">
          <span class="text-ink-soft">{{ i.openAmount > 0 ? `offen ${eur(i.openAmount, i.currency)}` : 'beglichen' }}</span>
          <span class="tabular-nums font-medium text-ink">{{ eur(i.totalGross, i.currency) }}</span>
        </div>
      </button>
      <p v-if="!invoices.length" class="py-6 text-center text-sm text-ink-soft">Keine Rechnungen gefunden.</p>
    </div>
  </AppShell>
</template>
