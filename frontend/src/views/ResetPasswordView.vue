<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'

const route = useRoute()
const router = useRouter()
const token = String(route.query.token || '')

const password = ref('')
const repeat = ref('')
const loading = ref(false)
const error = ref('')
const done = ref(false)

async function submit() {
  error.value = ''
  if (password.value.length < 8) {
    error.value = 'Das Passwort muss mindestens 8 Zeichen lang sein.'
    return
  }
  if (password.value !== repeat.value) {
    error.value = 'Die Passwörter stimmen nicht überein.'
    return
  }
  loading.value = true
  try {
    await api.post('/api/password/reset', { token, password: password.value })
    done.value = true
    setTimeout(() => router.push('/login'), 1800)
  } catch (e: any) {
    error.value = e.response?.data?.error || 'Zurücksetzen fehlgeschlagen.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-svh items-center justify-center bg-sand-50 px-4">
    <form class="w-full max-w-sm rounded-2xl border border-line bg-paper p-8 shadow-sm" @submit.prevent="submit">
      <img src="/logo.svg" alt="wohlbekannt" class="mb-2 h-12 w-auto" />
      <p class="mb-6 text-sm text-ink-soft">Neues Passwort vergeben</p>

      <template v-if="!done">
        <p v-if="!token" class="mb-4 text-sm text-red-600">Ungültiger Link.</p>
        <template v-else>
          <label class="label">Neues Passwort</label>
          <input v-model="password" type="password" autocomplete="new-password" class="input mb-4" placeholder="mind. 8 Zeichen" />
          <label class="label">Passwort wiederholen</label>
          <input v-model="repeat" type="password" autocomplete="new-password" class="input mb-4" />
          <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>
          <button type="submit" :disabled="loading" class="btn-primary w-full">{{ loading ? 'Speichern…' : 'Passwort speichern' }}</button>
        </template>
      </template>

      <p v-else class="text-sm text-green-700">Passwort geändert. Sie werden zur Anmeldung weitergeleitet …</p>

      <div class="mt-4 text-center">
        <RouterLink to="/login" class="text-sm text-ink-soft hover:text-ink">Zurück zur Anmeldung</RouterLink>
      </div>
    </form>
  </div>
</template>
