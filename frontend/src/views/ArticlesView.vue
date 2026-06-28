<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import Modal from '../components/Modal.vue'
import api from '../api'
import { centsToInput, eur, toCents } from '../lib/format'

const articles = ref<any[]>([])
const search = ref('')
const showModal = ref(false)
const saving = ref(false)
const form = reactive<any>(blank())
const priceInput = ref('0,00')

function blank() {
  return { id: null, name: '', description: '', unit: 'Stk', unitPrice: 0, vatRate: '20.00', taxCategory: 'standard', category: '', active: true }
}

async function load() {
  const { data } = await api.get('/api/articles', { params: { q: search.value } })
  articles.value = data
}

function openNew() {
  Object.assign(form, blank())
  priceInput.value = '0,00'
  showModal.value = true
}
function openEdit(a: any) {
  Object.assign(form, JSON.parse(JSON.stringify(a)))
  priceInput.value = centsToInput(a.unitPrice)
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    form.unitPrice = toCents(priceInput.value)
    if (form.id) await api.put(`/api/articles/${form.id}`, form)
    else await api.post('/api/articles', form)
    showModal.value = false
    await load()
  } finally {
    saving.value = false
  }
}
async function remove(a: any) {
  if (!confirm(`Artikel "${a.name}" löschen?`)) return
  await api.delete(`/api/articles/${a.id}`)
  await load()
}

onMounted(load)
</script>

<template>
  <AppShell>
    <template #breadcrumb>Artikel & Pakete</template>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">Artikel & Pakete</h1>
      <button class="btn-primary" @click="openNew">+ Neuer Artikel</button>
    </div>
    <div class="mb-4"><input v-model="search" class="input max-w-xs" placeholder="Suchen …" @input="load" /></div>

    <!-- desktop table -->
    <div class="hidden card overflow-hidden lg:block">
      <div class="table-wrap">
      <table class="w-full">
        <thead class="border-b border-line bg-sand-50/60">
          <tr><th class="th">Bezeichnung</th><th class="th">Kategorie</th><th class="th">Einheit</th><th class="th text-right">Preis (brutto)</th><th class="th text-right">USt</th><th class="th"></th></tr>
        </thead>
        <tbody>
          <tr v-for="a in articles" :key="a.id" class="border-b border-line last:border-0 hover:bg-sand-50/40">
            <td class="td font-medium">{{ a.name }}</td>
            <td class="td text-ink-soft">{{ a.category }}</td>
            <td class="td text-ink-soft">{{ a.unit }}</td>
            <td class="td text-right tabular-nums">{{ eur(a.unitPrice) }}</td>
            <td class="td text-right tabular-nums text-ink-soft">{{ a.vatRate }}%</td>
            <td class="td text-right whitespace-nowrap">
              <button class="btn-ghost" @click="openEdit(a)">Bearbeiten</button>
              <button class="btn-ghost text-red-600" @click="remove(a)">Löschen</button>
            </td>
          </tr>
          <tr v-if="!articles.length"><td class="td text-ink-soft" colspan="6">Keine Artikel gefunden.</td></tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- mobile cards -->
    <div class="space-y-3 lg:hidden">
      <div v-for="a in articles" :key="a.id" class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="font-medium text-ink">{{ a.name }}</div>
          <div class="shrink-0 tabular-nums font-medium text-ink">{{ eur(a.unitPrice) }}</div>
        </div>
        <div class="mt-0.5 text-xs text-ink-soft">{{ [a.category, a.unit, a.vatRate + '% USt'].filter(Boolean).join(' · ') }}</div>
        <div class="mt-3 flex gap-2 border-t border-line pt-3">
          <button class="btn-secondary flex-1" @click="openEdit(a)">Bearbeiten</button>
          <button class="btn-ghost text-red-600" @click="remove(a)">Löschen</button>
        </div>
      </div>
      <p v-if="!articles.length" class="py-6 text-center text-sm text-ink-soft">Keine Artikel gefunden.</p>
    </div>

    <Modal v-if="showModal" :title="form.id ? 'Artikel bearbeiten' : 'Neuer Artikel'" @close="showModal = false">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2"><label class="label">Bezeichnung</label><input v-model="form.name" class="input" /></div>
        <div class="col-span-2"><label class="label">Beschreibung</label><textarea v-model="form.description" rows="3" class="input"></textarea></div>
        <div><label class="label">Kategorie</label><input v-model="form.category" class="input" /></div>
        <div><label class="label">Einheit</label><input v-model="form.unit" class="input" placeholder="Stk / Personen / pauschal" /></div>
        <div><label class="label">Preis brutto (EUR)</label><input v-model="priceInput" class="input text-right tabular-nums" /></div>
        <div><label class="label">USt-Satz (%)</label><input v-model="form.vatRate" class="input text-right" /></div>
      </div>
      <template #actions>
        <button class="btn-secondary" @click="showModal = false">Abbrechen</button>
        <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Speichern…' : 'Speichern' }}</button>
      </template>
    </Modal>
  </AppShell>
</template>
