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
  <div class="flex min-h-svh">
    <!-- form side -->
    <div class="flex w-full flex-col justify-center bg-paper px-6 py-12 lg:w-[460px] lg:px-14">
      <div class="mx-auto w-full max-w-sm">
        <img src="/logo.svg" alt="wohlbekannt" class="mb-10 h-12 w-auto" />
        <h1 class="text-2xl font-semibold tracking-tight text-ink">Willkommen zurück</h1>
        <p class="mb-8 mt-1 text-sm text-ink-soft">Bitte melden Sie sich an.</p>

        <form @submit.prevent="submit">
          <label class="label">E-Mail</label>
          <input v-model="email" type="email" required autocomplete="username" class="input mb-4" />

          <label class="label">Passwort</label>
          <input v-model="password" type="password" required autocomplete="current-password" class="input mb-4" />

          <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? 'Anmelden…' : 'Anmelden' }}
          </button>

          <div class="mt-4 text-center">
            <RouterLink to="/passwort-vergessen" class="text-sm text-ink-soft hover:text-ink">Passwort vergessen?</RouterLink>
          </div>
        </form>
      </div>
    </div>

    <!-- image side -->
    <div class="relative hidden flex-1 lg:block">
      <img src="/login-bg.webp" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/10 to-transparent"></div>
      <div class="absolute inset-x-0 bottom-0 p-12">
        <p class="max-w-md text-2xl font-medium leading-snug text-paper drop-shadow">
          Mobile Bar &amp; Event-Catering aus Tirol.
        </p>
        <p class="mt-2 text-sm text-paper/80">Angebote &amp; Rechnungen — alles an einem Ort.</p>
      </div>
    </div>
  </div>
</template>
