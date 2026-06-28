<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import api from '../api'
import { eur, formatDate } from '../lib/format'

const router = useRouter()
const quotes = ref<any[]>([])

async function load() {
  const { data } = await api.get('/api/quotes')
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

    <div class="card overflow-hidden">
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
          <tr v-if="!quotes.length"><td class="td text-ink-soft" colspan="5">Noch keine Angebote.</td></tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>
