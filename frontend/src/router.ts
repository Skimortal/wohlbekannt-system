import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('./views/LoginView.vue'), meta: { public: true } },
    { path: '/', name: 'dashboard', component: () => import('./views/DashboardView.vue') },
    { path: '/kunden', name: 'customers', component: () => import('./views/CustomersView.vue') },
    { path: '/artikel', name: 'articles', component: () => import('./views/ArticlesView.vue') },
    { path: '/angebote', name: 'quotes', component: () => import('./views/QuotesView.vue') },
    { path: '/angebote/neu', name: 'quote-new', component: () => import('./views/QuoteEditorView.vue') },
    { path: '/angebote/:id', name: 'quote-edit', component: () => import('./views/QuoteEditorView.vue') },
    { path: '/rechnungen', name: 'invoices', component: () => import('./views/InvoicesView.vue') },
    { path: '/rechnungen/neu', name: 'invoice-new', component: () => import('./views/InvoiceEditorView.vue') },
    { path: '/rechnungen/:id', name: 'invoice-edit', component: () => import('./views/InvoiceEditorView.vue') },
    { path: '/benutzer', name: 'users', component: () => import('./views/UsersView.vue') },
    { path: '/einstellungen', name: 'settings', component: () => import('./views/SettingsView.vue') },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (!to.meta.public && !auth.isAuthenticated()) {
    return { name: 'login' }
  }
  if (to.name === 'login' && auth.isAuthenticated()) {
    return { name: 'dashboard' }
  }
})

export default router
