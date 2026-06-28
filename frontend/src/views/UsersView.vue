<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import Modal from '../components/Modal.vue'
import api from '../api'

const users = ref<any[]>([])
const meId = ref<number | null>(null)
const search = ref('')
const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return users.value
  return users.value.filter((u) => `${u.name} ${u.email}`.toLowerCase().includes(q))
})
const showModal = ref(false)
const saving = ref(false)
const error = ref('')
const form = reactive<any>(blank())

function blank() {
  return { id: null, name: '', email: '', role: 'user', active: true, password: '', passwordRepeat: '' }
}
const ROLE_LABEL: Record<string, string> = { admin: 'Administrator', user: 'Mitarbeiter' }

async function load() {
  const [u, me] = await Promise.all([api.get('/api/users'), api.get('/api/me')])
  users.value = u.data
  meId.value = me.data.id
}

function openNew() {
  Object.assign(form, blank())
  error.value = ''
  showModal.value = true
}
function openEdit(u: any) {
  Object.assign(form, { id: u.id, name: u.name, email: u.email, role: u.role, active: u.active, password: '' })
  error.value = ''
  showModal.value = true
}

async function save() {
  error.value = ''
  // Password required on create, optional on edit. Validate when set.
  if (!form.id && !form.password) {
    error.value = 'Bitte ein Passwort vergeben.'
    return
  }
  if (form.password) {
    if (form.password.length < 8) {
      error.value = 'Das Passwort muss mindestens 8 Zeichen lang sein.'
      return
    }
    if (form.password !== form.passwordRepeat) {
      error.value = 'Die Passwörter stimmen nicht überein.'
      return
    }
  }
  saving.value = true
  try {
    const payload: any = { name: form.name, email: form.email, role: form.role, active: form.active }
    if (form.password) payload.password = form.password
    if (form.id) await api.put(`/api/users/${form.id}`, payload)
    else await api.post('/api/users', payload)
    showModal.value = false
    await load()
  } catch (e: any) {
    error.value = e.response?.data?.error || 'Speichern fehlgeschlagen.'
  } finally {
    saving.value = false
  }
}

async function remove(u: any) {
  if (!confirm(`Benutzer "${u.name}" löschen?`)) return
  try {
    await api.delete(`/api/users/${u.id}`)
    await load()
  } catch (e: any) {
    alert(e.response?.data?.error || 'Löschen fehlgeschlagen.')
  }
}

onMounted(load)
</script>

<template>
  <AppShell>
    <template #breadcrumb>Benutzer</template>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">Benutzer & Rollen</h1>
      <button class="btn-primary" @click="openNew">+ Neuer Benutzer</button>
    </div>

    <div class="mb-4"><input v-model="search" class="input max-w-xs" placeholder="Suchen (Name, E-Mail) …" /></div>

    <!-- desktop table -->
    <div class="hidden card overflow-hidden lg:block">
      <div class="table-wrap">
      <table class="w-full">
        <thead class="border-b border-line bg-sand-50/60">
          <tr><th class="th">Name</th><th class="th">E-Mail</th><th class="th">Rolle</th><th class="th">Status</th><th class="th"></th></tr>
        </thead>
        <tbody>
          <tr v-for="u in filtered" :key="u.id" class="border-b border-line last:border-0 hover:bg-sand-50/40">
            <td class="td font-medium">{{ u.name }}<span v-if="u.id === meId" class="ml-2 text-xs text-ink-soft">(Sie)</span></td>
            <td class="td text-ink-soft">{{ u.email }}</td>
            <td class="td">{{ ROLE_LABEL[u.role] }}</td>
            <td class="td">
              <span v-if="u.active" class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">Aktiv</span>
              <span v-else class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200">Inaktiv</span>
            </td>
            <td class="td text-right whitespace-nowrap">
              <button class="btn-ghost" @click="openEdit(u)">Bearbeiten</button>
              <button v-if="u.id !== meId" class="btn-ghost text-red-600" @click="remove(u)">Löschen</button>
            </td>
          </tr>
          <tr v-if="!filtered.length"><td class="td text-ink-soft" colspan="5">Keine Benutzer gefunden.</td></tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- mobile cards -->
    <div class="space-y-3 lg:hidden">
      <div v-for="u in filtered" :key="u.id" class="card p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="font-medium text-ink">{{ u.name }}<span v-if="u.id === meId" class="ml-2 text-xs text-ink-soft">(Sie)</span></div>
            <div class="text-sm text-ink-soft">{{ u.email }}</div>
            <div class="mt-0.5 text-xs text-ink-soft">{{ ROLE_LABEL[u.role] }}</div>
          </div>
          <span v-if="u.active" class="shrink-0 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200">Aktiv</span>
          <span v-else class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200">Inaktiv</span>
        </div>
        <div class="mt-3 flex gap-2 border-t border-line pt-3">
          <button class="btn-secondary flex-1" @click="openEdit(u)">Bearbeiten</button>
          <button v-if="u.id !== meId" class="btn-ghost text-red-600" @click="remove(u)">Löschen</button>
        </div>
      </div>
      <p v-if="!filtered.length" class="py-6 text-center text-sm text-ink-soft">Keine Benutzer gefunden.</p>
    </div>

    <Modal v-if="showModal" :title="form.id ? 'Benutzer bearbeiten' : 'Neuer Benutzer'" @close="showModal = false">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">Name</label><input v-model="form.name" class="input" /></div>
        <div><label class="label">E-Mail</label><input v-model="form.email" type="email" class="input" /></div>
        <div>
          <label class="label">Rolle</label>
          <select v-model="form.role" class="input" :disabled="form.id === meId">
            <option value="user">Mitarbeiter</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div>
          <label class="label">Status</label>
          <label class="mt-2 flex items-center gap-2 text-sm text-ink">
            <input v-model="form.active" type="checkbox" :disabled="form.id === meId" /> aktiv
          </label>
        </div>
        <div>
          <label class="label">{{ form.id ? 'Neues Passwort' : 'Passwort' }}</label>
          <input v-model="form.password" type="password" autocomplete="new-password" class="input" :placeholder="form.id ? 'leer = unverändert' : 'mind. 8 Zeichen'" />
        </div>
        <div>
          <label class="label">Passwort wiederholen</label>
          <input v-model="form.passwordRepeat" type="password" autocomplete="new-password" class="input" />
        </div>
      </div>
      <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
      <template #actions>
        <button class="btn-secondary" @click="showModal = false">Abbrechen</button>
        <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Speichern…' : 'Speichern' }}</button>
      </template>
    </Modal>
  </AppShell>
</template>
