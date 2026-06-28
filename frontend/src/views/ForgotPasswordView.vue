<script setup lang="ts">
import { ref } from 'vue'
import api from '../api'

const email = ref('')
const loading = ref(false)
const message = ref('')
const devUrl = ref('')

async function submit() {
  loading.value = true
  message.value = ''
  devUrl.value = ''
  try {
    const { data } = await api.post('/api/password/forgot', { email: email.value })
    message.value = data.message
    if (data.devResetUrl) devUrl.value = data.devResetUrl
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-svh items-center justify-center bg-sand-50 px-4">
    <form class="w-full max-w-sm rounded-2xl border border-line bg-paper p-8 shadow-sm" @submit.prevent="submit">
      <img src="/logo.svg" alt="wohlbekannt" class="mb-2 h-12 w-auto" />
      <p class="mb-6 text-sm text-ink-soft">Passwort zurücksetzen</p>

      <template v-if="!message">
        <label class="label">E-Mail</label>
        <input v-model="email" type="email" required autocomplete="username" class="input mb-4" />
        <button type="submit" :disabled="loading" class="btn-primary w-full">
          {{ loading ? 'Senden…' : 'Link anfordern' }}
        </button>
      </template>

      <div v-else>
        <p class="mb-4 text-sm text-ink">{{ message }}</p>
        <p v-if="devUrl" class="mb-4 rounded-lg bg-sand-50 p-3 text-xs text-ink-soft">
          <span class="font-medium text-ink">Dev:</span>
          <RouterLink :to="devUrl.replace(/^https?:\/\/[^/]+/, '')" class="break-all text-blue-700 underline">Link zum Zurücksetzen öffnen</RouterLink>
        </p>
      </div>

      <div class="mt-4 text-center">
        <RouterLink to="/login" class="text-sm text-ink-soft hover:text-ink">Zurück zur Anmeldung</RouterLink>
      </div>
    </form>
  </div>
</template>
