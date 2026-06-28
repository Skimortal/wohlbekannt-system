<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import Modal from '../components/Modal.vue'
import api from '../api'
import { formatDate } from '../lib/format'

const customers = ref<any[]>([])
const search = ref('')
const showModal = ref(false)
const saving = ref(false)
const form = reactive<any>(blank())

function blank() {
  return {
    id: null, type: 'person', companyName: '', firstName: '', lastName: '',
    contactPerson: '', email: '', phone: '', vatId: '',
    address: { street: '', postalCode: '', city: '', countryCode: 'AT' },
  }
}

async function load() {
  const { data } = await api.get('/api/customers', { params: { q: search.value } })
  customers.value = data
}

function openNew() {
  Object.assign(form, blank())
  showModal.value = true
}

function openEdit(c: any) {
  Object.assign(form, JSON.parse(JSON.stringify(c)))
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (form.id) await api.put(`/api/customers/${form.id}`, form)
    else await api.post('/api/customers', form)
    showModal.value = false
    await load()
  } finally {
    saving.value = false
  }
}

async function remove(c: any) {
  if (!confirm(`Kunde "${c.displayName}" löschen?`)) return
  await api.delete(`/api/customers/${c.id}`)
  await load()
}

onMounted(load)
</script>

<template>
  <AppShell>
    <template #breadcrumb>Kunden</template>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">Kunden</h1>
      <button class="btn-primary" @click="openNew">+ Neuer Kunde</button>
    </div>

    <div class="mb-4">
      <input v-model="search" class="input max-w-xs" placeholder="Suchen …" @input="load" />
    </div>

    <div class="card overflow-hidden">
      <table class="w-full">
        <thead class="border-b border-line bg-sand-50/60">
          <tr><th class="th">Nr.</th><th class="th">Name</th><th class="th">E-Mail</th><th class="th">Ort</th><th class="th">Angelegt</th><th class="th"></th></tr>
        </thead>
        <tbody>
          <tr v-for="c in customers" :key="c.id" class="border-b border-line last:border-0 hover:bg-sand-50/40">
            <td class="td font-medium">{{ c.customerNumber }}</td>
            <td class="td">{{ c.displayName }}</td>
            <td class="td text-ink-soft">{{ c.email }}</td>
            <td class="td text-ink-soft">{{ c.address.city }}</td>
            <td class="td text-ink-soft">{{ formatDate(c.createdAt) }}</td>
            <td class="td text-right whitespace-nowrap">
              <button class="btn-ghost" @click="openEdit(c)">Bearbeiten</button>
              <button class="btn-ghost text-red-600" @click="remove(c)">Löschen</button>
            </td>
          </tr>
          <tr v-if="!customers.length"><td class="td text-ink-soft" colspan="6">Noch keine Kunden.</td></tr>
        </tbody>
      </table>
    </div>

    <Modal v-if="showModal" :title="form.id ? 'Kunde bearbeiten' : 'Neuer Kunde'" @close="showModal = false">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="label">Typ</label>
          <select v-model="form.type" class="input">
            <option value="person">Person</option>
            <option value="company">Firma</option>
          </select>
        </div>
        <div v-if="form.type === 'company'">
          <label class="label">Firmenname</label>
          <input v-model="form.companyName" class="input" />
        </div>
        <div>
          <label class="label">Vorname</label>
          <input v-model="form.firstName" class="input" />
        </div>
        <div>
          <label class="label">Nachname</label>
          <input v-model="form.lastName" class="input" />
        </div>
        <div>
          <label class="label">Ansprechpartner</label>
          <input v-model="form.contactPerson" class="input" />
        </div>
        <div>
          <label class="label">USt-ID</label>
          <input v-model="form.vatId" class="input" placeholder="ATU…" />
        </div>
        <div>
          <label class="label">E-Mail</label>
          <input v-model="form.email" class="input" />
        </div>
        <div>
          <label class="label">Telefon</label>
          <input v-model="form.phone" class="input" />
        </div>
        <div class="col-span-2">
          <label class="label">Straße</label>
          <input v-model="form.address.street" class="input" />
        </div>
        <div>
          <label class="label">PLZ</label>
          <input v-model="form.address.postalCode" class="input" />
        </div>
        <div>
          <label class="label">Ort</label>
          <input v-model="form.address.city" class="input" />
        </div>
        <div>
          <label class="label">Land</label>
          <input v-model="form.address.countryCode" class="input" maxlength="2" />
        </div>
      </div>
      <template #actions>
        <button class="btn-secondary" @click="showModal = false">Abbrechen</button>
        <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Speichern…' : 'Speichern' }}</button>
      </template>
    </Modal>
  </AppShell>
</template>
