<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import api from '../api'
import { eur } from '../lib/format'

const stats = ref({ quotes: 0, openInvoices: 0, openAmount: 0, customers: 0 })

onMounted(async () => {
  const [quotes, invoices, customers] = await Promise.all([
    api.get('/api/quotes'),
    api.get('/api/invoices'),
    api.get('/api/customers'),
  ])
  const open = invoices.data.filter(
    (i: any) => i.type === 'invoice' && ['sent', 'partially_paid', 'overdue'].includes(i.status),
  )
  stats.value = {
    quotes: quotes.data.length,
    openInvoices: open.length,
    openAmount: open.reduce((s: number, i: any) => s + i.openAmount, 0),
    customers: customers.data.length,
  }
})
</script>

<template>
  <AppShell>
    <template #breadcrumb>Dashboard</template>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-ink">Übersicht</h1>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Angebote</div>
        <div class="mt-1 text-2xl font-semibold text-ink">{{ stats.quotes }}</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Offene Rechnungen</div>
        <div class="mt-1 text-2xl font-semibold text-ink">{{ stats.openInvoices }}</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Offener Betrag</div>
        <div class="mt-1 text-2xl font-semibold text-ink">{{ eur(stats.openAmount) }}</div>
      </div>
      <div class="card p-5">
        <div class="text-sm text-ink-soft">Kunden</div>
        <div class="mt-1 text-2xl font-semibold text-ink">{{ stats.customers }}</div>
      </div>
    </div>
  </AppShell>
</template>
