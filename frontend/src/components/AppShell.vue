<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const isAdmin = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get('/api/me')
    isAdmin.value = (data.roles || []).includes('ROLE_ADMIN')
  } catch {
    /* ignore */
  }
})

const nav = [
  { to: '/', label: 'Dashboard', exact: true },
  { to: '/kunden', label: 'Kunden' },
  { to: '/angebote', label: 'Angebote' },
  { to: '/rechnungen', label: 'Rechnungen' },
]
const adminNav = [
  { to: '/artikel', label: 'Artikel & Pakete' },
  { to: '/einstellungen', label: 'Einstellungen' },
]

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-svh bg-sand-50">
    <!-- sidebar -->
    <aside class="fixed inset-y-0 left-0 w-60 border-r border-line bg-paper px-4 py-5">
      <div class="mb-8 px-2">
        <img src="/logo.svg" alt="wohlbekannt" class="h-9 w-auto" />
      </div>
      <nav class="space-y-0.5">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="block rounded-lg px-3 py-2 text-sm text-ink-soft hover:bg-sand-50"
          active-class="bg-sand-100 !text-ink font-medium"
          :exact-active-class="item.exact ? 'bg-sand-100 !text-ink font-medium' : ''"
        >
          {{ item.label }}
        </RouterLink>
        <div class="px-3 pb-1 pt-5 text-xs font-medium uppercase tracking-wide text-ink-soft/60">Verwaltung</div>
        <RouterLink
          v-for="item in adminNav"
          :key="item.to"
          :to="item.to"
          class="block rounded-lg px-3 py-2 text-sm text-ink-soft hover:bg-sand-50"
          active-class="bg-sand-100 !text-ink font-medium"
        >
          {{ item.label }}
        </RouterLink>
        <RouterLink
          v-if="isAdmin"
          to="/benutzer"
          class="block rounded-lg px-3 py-2 text-sm text-ink-soft hover:bg-sand-50"
          active-class="bg-sand-100 !text-ink font-medium"
        >
          Benutzer
        </RouterLink>
      </nav>
    </aside>

    <!-- main -->
    <div class="ml-60 flex min-h-svh flex-1 flex-col">
      <header class="flex h-14 items-center justify-between border-b border-line bg-paper px-6">
        <div class="text-sm text-ink-soft"><slot name="breadcrumb" /></div>
        <button class="text-sm text-ink-soft hover:text-ink" @click="logout">Abmelden</button>
      </header>
      <main class="flex-1 px-6 py-8">
        <slot />
      </main>
    </div>
  </div>
</template>
