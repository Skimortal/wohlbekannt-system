<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch {
    error.value = 'Anmeldung fehlgeschlagen.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-svh items-center justify-center bg-sand-50 px-4">
    <form
      class="w-full max-w-sm rounded-2xl border border-line bg-paper p-8 shadow-sm"
      @submit.prevent="submit"
    >
      <img src="/logo.svg" alt="wohlbekannt" class="mb-2 h-12 w-auto" />
      <p class="mb-6 text-sm text-ink-soft">Angebote &amp; Rechnungen</p>

      <label class="mb-1 block text-sm font-medium text-ink">E-Mail</label>
      <input
        v-model="email"
        type="email"
        required
        autocomplete="username"
        class="mb-4 w-full rounded-lg border border-line px-3 py-2 outline-none focus:border-sand"
      />

      <label class="mb-1 block text-sm font-medium text-ink">Passwort</label>
      <input
        v-model="password"
        type="password"
        required
        autocomplete="current-password"
        class="mb-4 w-full rounded-lg border border-line px-3 py-2 outline-none focus:border-sand"
      />

      <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

      <button
        type="submit"
        :disabled="loading"
        class="w-full rounded-lg bg-ink py-2 font-medium text-paper transition hover:opacity-90 disabled:opacity-50"
      >
        {{ loading ? 'Anmelden…' : 'Anmelden' }}
      </button>
    </form>
  </div>
</template>
