<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const health = ref<string>('…')

onMounted(async () => {
  try {
    const { data } = await api.get('/api/health')
    health.value = data.status
  } catch {
    health.value = 'nicht erreichbar'
  }
})

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-svh bg-sand-50">
    <header class="flex items-center justify-between border-b border-line bg-paper px-6 py-4">
      <span class="text-lg font-semibold tracking-tight text-ink">wohlbekannt</span>
      <button class="text-sm text-ink-soft hover:text-ink" @click="logout">Abmelden</button>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
      <h1 class="mb-2 text-2xl font-semibold tracking-tight text-ink">Übersicht</h1>
      <p class="text-ink-soft">Phase 1: Angebote &amp; Rechnungen — Grundgerüst läuft.</p>
      <p class="mt-4 text-sm text-ink-soft">Backend-Health: {{ health }}</p>
    </main>
  </div>
</template>
