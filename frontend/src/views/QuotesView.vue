<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import api from '../api'
import { eur, formatDate } from '../lib/format'

const router = useRouter()
const quotes = ref<any[]>([])
const search = ref('')
const status = ref('')

const STATUS = [
  { v: '', l: 'Alle Status' },
  { v: 'draft', l: 'Entwurf' },
  { v: 'sent', l: 'Versendet' },
  { v: 'accepted', l: 'Angenommen' },
  { v: 'declined', l: 'Abgelehnt' },
  { v: 'expired', l: 'Abgelaufen' },
]

async function load() {
  const { data } = await api.get('/api/quotes', { params: { q: search.value, status: status.value } })
  quotes.value = data
}
onMounted(load)
</script>

<template>
  <AppShell>
    <template #breadcrumb>Angebote</template>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">Angebote</h1>
      <button class="btn-primary" @click="router.push('/angebote/neu')">+ Neues Angebot</button>
    </div>

    <div class="mb-4 flex flex-col gap-2 sm:flex-row">
      <input v-model="search" class="input sm:max-w-xs" placeholder="Suchen (Nr., Kunde) …" @input="load" />
      <select v-model="status" class="input sm:max-w-[200px]" @change="load">
        <option v-for="s in STATUS" :key="s.v" :value="s.v">{{ s.l }}</option>
      </select>
    </div>

    <!-- desktop table -->
    <div class="hidden card overflow-hidden lg:block">
      <div class="table-wrap">
        <table class="w-full">
          <thead class="border-b border-line bg-sand-50/60">
            <tr><th class="th">Nr.</th><th class="th">Kunde</th><th class="th">Datum</th><th class="th">Status</th><th class="th text-right">Betrag brutto</th></tr>
          </thead>
          <tbody>
            <tr v-for="q in quotes" :key="q.id" class="cursor-pointer border-b border-line last:border-0 hover:bg-sand-50/40" @click="router.push(`/angebote/${q.id}`)">
              <td class="td font-medium">{{ q.number ?? '—' }}</td>
              <td class="td">{{ q.recipientName }}</td>
              <td class="td text-ink-soft">{{ formatDate(q.issueDate) }}</td>
              <td class="td"><StatusBadge :status="q.status" kind="quote" /></td>
              <td class="td text-right tabular-nums">{{ eur(q.totalGross, q.currency) }}</td>
            </tr>
            <tr v-if="!quotes.length"><td class="td text-ink-soft" colspan="5">Keine Angebote gefunden.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- mobile cards -->
    <div class="space-y-3 lg:hidden">
      <button
        v-for="q in quotes"
        :key="q.id"
        class="card w-full p-4 text-left"
        @click="router.push(`/angebote/${q.id}`)"
      >
        <div class="flex items-center justify-between">
          <span class="font-medium text-ink">{{ q.number ?? 'Entwurf' }}</span>
          <StatusBadge :status="q.status" kind="quote" />
        </div>
        <div class="mt-1 text-sm text-ink">{{ q.recipientName }}</div>
        <div class="mt-2 flex items-center justify-between text-sm text-ink-soft">
          <span>{{ formatDate(q.issueDate) }}</span>
          <span class="tabular-nums font-medium text-ink">{{ eur(q.totalGross, q.currency) }}</span>
        </div>
      </button>
      <p v-if="!quotes.length" class="py-6 text-center text-sm text-ink-soft">Keine Angebote gefunden.</p>
    </div>
  </AppShell>
</template>
