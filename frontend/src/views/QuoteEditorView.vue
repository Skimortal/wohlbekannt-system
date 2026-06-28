<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import Modal from '../components/Modal.vue'
import api from '../api'
import { centsToInput, eur, isoDate, toCents } from '../lib/format'
import { computeTotals, parseQty, type EditorItem } from '../lib/totals'
import { openPdf } from '../lib/pdf'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.params.id === undefined)

const customers = ref<any[]>([])
const articles = ref<any[]>([])
const saving = ref(false)
const id = ref<number | null>(null)
const showInvoiceModal = ref(false)
const optionalItems = ref<{ id: number; title: string; lineGross: number }[]>([])
const selectedOptional = reactive<Record<number, boolean>>({})

const form = reactive<any>({
  customerId: null,
  recipientName: '',
  contactPerson: '',
  currency: 'EUR',
  pricesIncludeVat: true,
  issueDate: new Date().toISOString().slice(0, 10),
  validUntil: '',
  introText: '',
  outroText: '',
  status: 'draft',
  number: null,
  items: [] as EditorItem[],
})

const totals = computed(() => computeTotals(form.items, form.pricesIncludeVat))
const isDraft = computed(() => form.status === 'draft')

function newItem(optional = false): EditorItem {
  return { optional, title: '', description: '', quantity: '1', unit: 'Stk', priceInput: '0,00', vatRate: form.defaultVatRate || '20.00', taxCategory: 'standard' }
}

function addItem(optional = false) {
  form.items.push(newItem(optional))
}
function addFromArticle(e: Event) {
  const aid = Number((e.target as HTMLSelectElement).value)
  ;(e.target as HTMLSelectElement).value = ''
  const a = articles.value.find((x) => x.id === aid)
  if (!a) return
  form.items.push({ optional: false, title: a.name, description: a.description || '', quantity: '1', unit: a.unit, priceInput: centsToInput(a.unitPrice), vatRate: a.vatRate, taxCategory: a.taxCategory })
}
function removeItem(i: number) {
  form.items.splice(i, 1)
}
function move(i: number, dir: number) {
  const j = i + dir
  if (j < 0 || j >= form.items.length) return
  const [it] = form.items.splice(i, 1)
  form.items.splice(j, 0, it)
}
function onCustomerChange() {
  const c = customers.value.find((x) => x.id === form.customerId)
  if (c) form.recipientName = c.displayName
}

function payload() {
  return {
    customerId: form.customerId,
    recipientName: form.recipientName,
    contactPerson: form.contactPerson,
    pricesIncludeVat: form.pricesIncludeVat,
    issueDate: form.issueDate,
    validUntil: form.validUntil || null,
    introText: form.introText,
    outroText: form.outroText,
    items: form.items.map((it: EditorItem, idx: number) => ({
      position: idx,
      optional: it.optional,
      title: it.title,
      description: it.description || null,
      quantity: String(parseQty(it.quantity)),
      unit: it.unit,
      unitPrice: toCents(it.priceInput),
      vatRate: String(it.vatRate).replace(',', '.'),
      taxCategory: it.taxCategory,
    })),
  }
}

async function save(): Promise<boolean> {
  saving.value = true
  try {
    if (id.value) {
      const { data } = await api.put(`/api/quotes/${id.value}`, payload())
      apply(data)
    } else {
      const { data } = await api.post('/api/quotes', payload())
      id.value = data.id
      apply(data)
      router.replace(`/angebote/${data.id}`)
    }
    return true
  } finally {
    saving.value = false
  }
}

async function send() {
  if (!(await save())) return
  const { data } = await api.post(`/api/quotes/${id.value}/send`)
  apply(data)
}
async function decide(action: 'accept' | 'decline') {
  const { data } = await api.post(`/api/quotes/${id.value}/${action}`)
  apply(data)
}
function createInvoice() {
  if (optionalItems.value.length) {
    optionalItems.value.forEach((it) => (selectedOptional[it.id] = false))
    showInvoiceModal.value = true
  } else {
    doCreateInvoice([])
  }
}
async function doCreateInvoice(includeOptionalItemIds: number[]) {
  const { data } = await api.post(`/api/invoices/from-quote/${id.value}`, { includeOptionalItemIds })
  router.push(`/rechnungen/${data.id}`)
}
function confirmCreateInvoice() {
  const ids = optionalItems.value.filter((it) => selectedOptional[it.id]).map((it) => it.id)
  showInvoiceModal.value = false
  doCreateInvoice(ids)
}

function apply(q: any) {
  id.value = q.id
  form.status = q.status
  form.number = q.number
  form.customerId = q.customerId
  form.recipientName = q.recipientName
  form.contactPerson = q.contactPerson
  form.currency = q.currency
  form.pricesIncludeVat = q.pricesIncludeVat
  form.issueDate = isoDate(q.issueDate)
  form.validUntil = isoDate(q.validUntil)
  form.introText = q.introText || ''
  form.outroText = q.outroText || ''
  form.items = (q.items || []).map((it: any): EditorItem => ({
    optional: it.optional, title: it.title, description: it.description || '',
    quantity: String(it.quantity).replace('.', ','), unit: it.unit,
    priceInput: centsToInput(it.unitPrice), vatRate: it.vatRate, taxCategory: it.taxCategory,
  }))
  optionalItems.value = (q.items || [])
    .filter((it: any) => it.optional)
    .map((it: any) => ({ id: it.id, title: it.title, lineGross: it.lineGross }))
}

onMounted(async () => {
  const [cs, as, settings] = await Promise.all([
    api.get('/api/customers'),
    api.get('/api/articles', { params: { active: true } }),
    api.get('/api/company-settings'),
  ])
  customers.value = cs.data
  articles.value = as.data
  form.defaultVatRate = settings.data.defaultVatRate
  if (isNew.value) {
    form.introText = settings.data.quoteIntroText || ''
    form.outroText = settings.data.quoteOutroText || ''
    form.contactPerson = settings.data.managingDirector || ''
    const d = new Date()
    d.setDate(d.getDate() + (settings.data.defaultQuoteValidityDays || 30))
    form.validUntil = d.toISOString().slice(0, 10)
  } else {
    const { data } = await api.get(`/api/quotes/${route.params.id}`)
    apply(data)
  }
})
</script>

<template>
  <AppShell>
    <template #breadcrumb>Angebote / {{ form.number ?? 'Neu' }}</template>

    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ form.number ? `Angebot ${form.number}` : 'Neues Angebot' }}</h1>
        <StatusBadge :status="form.status" kind="quote" />
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="id" class="btn-secondary" @click="openPdf(`/api/quotes/${id}/pdf`)">PDF öffnen</button>
        <button v-if="isDraft" class="btn-secondary" :disabled="saving" @click="save">Entwurf speichern</button>
        <button v-if="isDraft" class="btn-primary" :disabled="saving" @click="send">Versenden</button>
        <button v-if="form.status === 'sent'" class="btn-accent" @click="decide('accept')">Annehmen</button>
        <button v-if="form.status === 'sent'" class="btn-ghost text-red-600" @click="decide('decline')">Ablehnen</button>
        <button v-if="form.status === 'accepted'" class="btn-primary" @click="createInvoice">Rechnung erstellen</button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- left: header fields -->
      <div class="card space-y-4 p-6 lg:col-span-1">
        <div>
          <label class="label">Kunde</label>
          <select v-model="form.customerId" class="input" :disabled="!isDraft" @change="onCustomerChange">
            <option :value="null">— manuell —</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.displayName }} ({{ c.customerNumber }})</option>
          </select>
        </div>
        <div><label class="label">Empfänger</label><input v-model="form.recipientName" class="input" :disabled="!isDraft" /></div>
        <div><label class="label">Ansprechpartner</label><input v-model="form.contactPerson" class="input" :disabled="!isDraft" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Datum</label><input v-model="form.issueDate" type="date" class="input" :disabled="!isDraft" /></div>
          <div><label class="label">Gültig bis</label><input v-model="form.validUntil" type="date" class="input" :disabled="!isDraft" /></div>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink">
          <input v-model="form.pricesIncludeVat" type="checkbox" :disabled="!isDraft" /> Preise inkl. USt (brutto)
        </label>
        <div><label class="label">Einleitungstext</label><textarea v-model="form.introText" rows="3" class="input" :disabled="!isDraft"></textarea></div>
        <div><label class="label">Schlusstext</label><textarea v-model="form.outroText" rows="3" class="input" :disabled="!isDraft"></textarea></div>
      </div>

      <!-- right: positions + totals -->
      <div class="space-y-6 lg:col-span-2">
        <div class="card p-6">
          <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-ink">Positionen</h2>
            <div v-if="isDraft" class="flex flex-wrap items-center gap-2">
              <select class="input !w-auto py-1.5 text-sm" @change="addFromArticle">
                <option value="">+ aus Katalog …</option>
                <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>
              <button class="btn-secondary" @click="addItem(false)">+ Position</button>
              <button class="btn-secondary" @click="addItem(true)">+ Optional</button>
            </div>
          </div>

          <div v-for="(it, i) in (form.items as EditorItem[])" :key="i" class="mb-3 rounded-xl border p-3" :class="it.optional ? 'border-sand-200 bg-sand-50' : 'border-line'">
            <div class="mb-2 flex items-center gap-2">
              <span v-if="it.optional" class="rounded border border-sand bg-sand-100 px-1.5 py-0.5 text-[10px] font-semibold text-[#8a5a2e]">Opt.</span>
              <span v-else class="text-xs text-ink-soft">Pos. {{ i + 1 }}</span>
              <input v-model="it.title" class="input flex-1" placeholder="Bezeichnung" :disabled="!isDraft" />
              <template v-if="isDraft">
                <button class="btn-ghost px-2" title="hoch" @click="move(i, -1)">↑</button>
                <button class="btn-ghost px-2" title="runter" @click="move(i, 1)">↓</button>
                <button class="btn-ghost px-2 text-red-600" @click="removeItem(i)">✕</button>
              </template>
            </div>
            <textarea v-model="it.description" rows="2" class="input mb-2 text-sm" placeholder="Beschreibung (optional)" :disabled="!isDraft"></textarea>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <div><label class="label !text-xs">Menge</label><input v-model="it.quantity" class="input text-right tabular-nums" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">Einheit</label><input v-model="it.unit" class="input" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">EP ({{ form.pricesIncludeVat ? 'brutto' : 'netto' }})</label><input v-model="it.priceInput" class="input text-right tabular-nums" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">USt %</label><input v-model="it.vatRate" class="input text-right" :disabled="!isDraft" /></div>
            </div>
            <label v-if="isDraft" class="mt-2 flex items-center gap-1.5 text-xs text-ink-soft">
              <input v-model="it.optional" type="checkbox" /> optionale Position
            </label>
          </div>

          <p v-if="!form.items.length" class="py-6 text-center text-sm text-ink-soft">Noch keine Positionen.</p>
        </div>

        <!-- totals -->
        <div class="card ml-auto max-w-md p-6">
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-ink-soft">Gesamtbetrag netto</span><span class="tabular-nums">{{ eur(totals.net, form.currency) }}</span></div>
            <div v-for="g in totals.breakdown" :key="g.rate" class="flex justify-between">
              <span class="text-ink-soft">zzgl. USt {{ g.rate }}%</span><span class="tabular-nums">{{ eur(g.tax, form.currency) }}</span>
            </div>
            <div class="flex justify-between border-t border-ink pt-2 font-semibold"><span>Gesamtbetrag brutto</span><span class="tabular-nums">{{ eur(totals.gross, form.currency) }}</span></div>
            <div v-if="totals.optionalGross > 0" class="flex justify-between pt-1 text-ink-soft">
              <span>Summe optionaler Positionen brutto</span><span class="tabular-nums">{{ eur(totals.optionalGross, form.currency) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal v-if="showInvoiceModal" title="Rechnung erstellen" @close="showInvoiceModal = false">
      <p class="mb-3 text-sm text-ink-soft">
        Welche optionalen Positionen hat der Kunde gebucht? Ausgewählte werden als reguläre
        Rechnungspositionen übernommen.
      </p>
      <div class="space-y-2">
        <label
          v-for="it in optionalItems"
          :key="it.id"
          class="flex items-center justify-between rounded-lg border border-line px-3 py-2 text-sm"
        >
          <span class="flex items-center gap-2">
            <input v-model="selectedOptional[it.id]" type="checkbox" />
            {{ it.title }}
          </span>
          <span class="tabular-nums text-ink-soft">{{ eur(it.lineGross, form.currency) }}</span>
        </label>
      </div>
      <template #actions>
        <button class="btn-secondary" @click="showInvoiceModal = false">Abbrechen</button>
        <button class="btn-primary" @click="confirmCreateInvoice">Rechnung erstellen</button>
      </template>
    </Modal>
  </AppShell>
</template>
