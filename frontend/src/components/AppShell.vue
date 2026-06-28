<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const isAdmin = ref(false)
const drawerOpen = ref(false)

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
  <div class="min-h-svh bg-sand-50">
    <!-- backdrop (mobile) -->
    <div v-if="drawerOpen" class="fixed inset-0 z-30 bg-ink/30 lg:hidden" @click="drawerOpen = false"></div>

    <!-- sidebar / drawer -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-60 border-r border-line bg-paper px-4 py-5 transition-transform duration-200 lg:translate-x-0"
      :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="mb-8 px-2">
        <img src="/logo.svg" alt="wohlbekannt" class="h-9 w-auto" />
      </div>
      <nav class="space-y-0.5" @click="drawerOpen = false">
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
    <div class="flex min-h-svh flex-col lg:ml-60">
      <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-line bg-paper px-4 lg:px-6">
        <div class="flex items-center gap-3">
          <button
            class="-ml-1 rounded-lg p-2 text-ink hover:bg-sand-50 lg:hidden"
            aria-label="Menü öffnen"
            @click="drawerOpen = true"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </button>
          <div class="text-sm text-ink-soft"><slot name="breadcrumb" /></div>
        </div>
        <button class="text-sm text-ink-soft hover:text-ink" @click="logout">Abmelden</button>
      </header>
      <main class="flex-1 px-4 py-6 lg:px-6 lg:py-8">
        <slot />
      </main>
    </div>
  </div>
</template>
