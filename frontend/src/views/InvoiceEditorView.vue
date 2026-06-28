<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import StatusBadge from '../components/StatusBadge.vue'
import Modal from '../components/Modal.vue'
import api from '../api'
import { centsToInput, eur, formatDate, isoDate, toCents } from '../lib/format'
import { computeTotals, parseQty, type EditorItem } from '../lib/totals'
import { openPdf } from '../lib/pdf'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.params.id === undefined)

const customers = ref<any[]>([])
const articles = ref<any[]>([])
const saving = ref(false)
const id = ref<number | null>(null)
const payments = ref<any[]>([])
const showPay = ref(false)
const pay = reactive({ amount: '', method: 'Überweisung', reference: '', paidAt: new Date().toISOString().slice(0, 10) })

const form = reactive<any>({
  customerId: null, recipientName: '', contactPerson: '', currency: 'EUR', pricesIncludeVat: true,
  issueDate: new Date().toISOString().slice(0, 10), dueDate: '', servicePeriodStart: '', servicePeriodEnd: '',
  introText: '', outroText: '', status: 'draft', number: null, type: 'invoice', paidAmount: 0, openAmount: 0,
  cancelsInvoiceId: null, items: [] as EditorItem[], defaultVatRate: '20.00',
})

const totals = computed(() => computeTotals(form.items, form.pricesIncludeVat))
const isDraft = computed(() => form.status === 'draft')
const TYPE_LABEL: Record<string, string> = { invoice: 'Rechnung', credit_note: 'Gutschrift', cancellation: 'Storno' }

function addItem() {
  form.items.push({ optional: false, title: '', description: '', quantity: '1', unit: 'Stk', priceInput: '0,00', vatRate: form.defaultVatRate, taxCategory: 'standard' })
}
function addFromArticle(e: Event) {
  const aid = Number((e.target as HTMLSelectElement).value)
  ;(e.target as HTMLSelectElement).value = ''
  const a = articles.value.find((x) => x.id === aid)
  if (!a) return
  form.items.push({ optional: false, title: a.name, description: a.description || '', quantity: '1', unit: a.unit, priceInput: centsToInput(a.unitPrice), vatRate: a.vatRate, taxCategory: a.taxCategory })
}
function removeItem(i: number) { form.items.splice(i, 1) }
function onCustomerChange() {
  const c = customers.value.find((x) => x.id === form.customerId)
  if (c) form.recipientName = c.displayName
}

function payload() {
  return {
    customerId: form.customerId, recipientName: form.recipientName, contactPerson: form.contactPerson,
    pricesIncludeVat: form.pricesIncludeVat, issueDate: form.issueDate, dueDate: form.dueDate || null,
    servicePeriodStart: form.servicePeriodStart || null, servicePeriodEnd: form.servicePeriodEnd || null,
    introText: form.introText, outroText: form.outroText,
    items: form.items.map((it: EditorItem, idx: number) => ({
      position: idx, title: it.title, description: it.description || null,
      quantity: String(parseQty(it.quantity)), unit: it.unit, unitPrice: toCents(it.priceInput),
      vatRate: String(it.vatRate).replace(',', '.'), taxCategory: it.taxCategory,
    })),
  }
}

async function save(): Promise<boolean> {
  saving.value = true
  try {
    if (id.value) { const { data } = await api.put(`/api/invoices/${id.value}`, payload()); apply(data) }
    else { const { data } = await api.post('/api/invoices', payload()); apply(data); router.replace(`/rechnungen/${data.id}`) }
    return true
  } finally { saving.value = false }
}
async function finalize() {
  if (!(await save())) return
  const { data } = await api.post(`/api/invoices/${id.value}/finalize`); apply(data)
}
async function savePayment() {
  const { data } = await api.post(`/api/invoices/${id.value}/payments`, { amount: toCents(pay.amount), method: pay.method, reference: pay.reference, paidAt: pay.paidAt })
  apply(data); showPay.value = false; pay.amount = ''; pay.reference = ''
}
async function storno() {
  if (!confirm('Diese Rechnung stornieren? Es wird eine Stornorechnung erstellt.')) return
  const { data } = await api.post(`/api/invoices/${id.value}/cancel`); router.push(`/rechnungen/${data.id}`)
}
async function creditNote() {
  if (!confirm('Gutschrift zu dieser Rechnung erstellen?')) return
  const { data } = await api.post(`/api/invoices/${id.value}/credit-note`); router.push(`/rechnungen/${data.id}`)
}

function apply(inv: any) {
  id.value = inv.id
  Object.assign(form, {
    status: inv.status, number: inv.number, type: inv.type, customerId: inv.customerId,
    recipientName: inv.recipientName, contactPerson: inv.contactPerson, currency: inv.currency,
    pricesIncludeVat: inv.pricesIncludeVat, issueDate: isoDate(inv.issueDate), dueDate: isoDate(inv.dueDate),
    servicePeriodStart: isoDate(inv.servicePeriodStart), servicePeriodEnd: isoDate(inv.servicePeriodEnd),
    introText: inv.introText || '', outroText: inv.outroText || '', paidAmount: inv.paidAmount,
    openAmount: inv.openAmount, cancelsInvoiceId: inv.cancelsInvoiceId,
  })
  form.items = (inv.items || []).map((it: any): EditorItem => ({
    optional: it.optional, title: it.title, description: it.description || '',
    quantity: String(it.quantity).replace('.', ','), unit: it.unit,
    priceInput: centsToInput(it.unitPrice), vatRate: it.vatRate, taxCategory: it.taxCategory,
  }))
  payments.value = inv.payments || []
}

onMounted(async () => {
  const [cs, as, settings] = await Promise.all([
    api.get('/api/customers'), api.get('/api/articles', { params: { active: true } }), api.get('/api/company-settings'),
  ])
  customers.value = cs.data
  articles.value = as.data
  form.defaultVatRate = settings.data.defaultVatRate
  if (isNew.value) {
    form.introText = settings.data.invoiceIntroText || ''
    form.outroText = settings.data.invoiceOutroText || ''
    form.contactPerson = settings.data.managingDirector || ''
  } else {
    const { data } = await api.get(`/api/invoices/${route.params.id}`); apply(data)
  }
})
</script>

<template>
  <AppShell>
    <template #breadcrumb>Rechnungen / {{ form.number ?? 'Neu' }}</template>

    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ form.number ? `${TYPE_LABEL[form.type]} ${form.number}` : 'Neue Rechnung' }}</h1>
        <StatusBadge :status="form.status" kind="invoice" />
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="id" class="btn-secondary" @click="openPdf(`/api/invoices/${id}/pdf`)">PDF öffnen</button>
        <button v-if="isDraft" class="btn-secondary" :disabled="saving" @click="save">Entwurf speichern</button>
        <button v-if="isDraft" class="btn-primary" :disabled="saving" @click="finalize">Festschreiben</button>
        <button v-if="!isDraft && form.status !== 'cancelled' && form.type === 'invoice'" class="btn-accent" @click="showPay = true">Zahlung erfassen</button>
        <button v-if="!isDraft && form.status !== 'cancelled' && form.type === 'invoice'" class="btn-ghost" @click="creditNote">Gutschrift</button>
        <button v-if="!isDraft && form.status !== 'cancelled' && form.type === 'invoice'" class="btn-ghost text-red-600" @click="storno">Stornieren</button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="card space-y-4 p-6 lg:col-span-1">
        <div>
          <label class="label">Kunde</label>
          <select v-model="form.customerId" class="input" :disabled="!isDraft" @change="onCustomerChange">
            <option :value="null">— manuell —</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.displayName }} ({{ c.customerNumber }})</option>
          </select>
        </div>
        <div><label class="label">Empfänger</label><input v-model="form.recipientName" class="input" :disabled="!isDraft" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Datum</label><input v-model="form.issueDate" type="date" class="input" :disabled="!isDraft" /></div>
          <div><label class="label">Fällig bis</label><input v-model="form.dueDate" type="date" class="input" :disabled="!isDraft" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="label">Leistung von</label><input v-model="form.servicePeriodStart" type="date" class="input" :disabled="!isDraft" /></div>
          <div><label class="label">bis</label><input v-model="form.servicePeriodEnd" type="date" class="input" :disabled="!isDraft" /></div>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink"><input v-model="form.pricesIncludeVat" type="checkbox" :disabled="!isDraft" /> Preise inkl. USt (brutto)</label>
        <div><label class="label">Einleitungstext</label><textarea v-model="form.introText" rows="2" class="input" :disabled="!isDraft"></textarea></div>
        <div><label class="label">Schlusstext</label><textarea v-model="form.outroText" rows="2" class="input" :disabled="!isDraft"></textarea></div>

        <div v-if="payments.length" class="border-t border-line pt-3">
          <div class="label">Zahlungen</div>
          <div v-for="p in payments" :key="p.id" class="flex justify-between text-sm text-ink-soft">
            <span>{{ formatDate(p.paidAt) }} · {{ p.method }}</span><span class="tabular-nums">{{ eur(p.amount, form.currency) }}</span>
          </div>
        </div>
      </div>

      <div class="space-y-6 lg:col-span-2">
        <div class="card p-6">
          <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold text-ink">Positionen</h2>
            <div v-if="isDraft" class="flex flex-wrap items-center gap-2">
              <select class="input !w-auto py-1.5 text-sm" @change="addFromArticle">
                <option value="">+ aus Katalog …</option>
                <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>
              <button class="btn-secondary" @click="addItem">+ Position</button>
            </div>
          </div>

          <div v-for="(it, i) in (form.items as EditorItem[])" :key="i" class="mb-3 rounded-xl border border-line p-3">
            <div class="mb-2 flex items-center gap-2">
              <span class="text-xs text-ink-soft">Pos. {{ i + 1 }}</span>
              <input v-model="it.title" class="input flex-1" placeholder="Bezeichnung" :disabled="!isDraft" />
              <button v-if="isDraft" class="btn-ghost px-2 text-red-600" @click="removeItem(i)">✕</button>
            </div>
            <textarea v-model="it.description" rows="2" class="input mb-2 text-sm" placeholder="Beschreibung (optional)" :disabled="!isDraft"></textarea>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <div><label class="label !text-xs">Menge</label><input v-model="it.quantity" class="input text-right tabular-nums" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">Einheit</label><input v-model="it.unit" class="input" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">EP ({{ form.pricesIncludeVat ? 'brutto' : 'netto' }})</label><input v-model="it.priceInput" class="input text-right tabular-nums" :disabled="!isDraft" /></div>
              <div><label class="label !text-xs">USt %</label><input v-model="it.vatRate" class="input text-right" :disabled="!isDraft" /></div>
            </div>
          </div>
          <p v-if="!form.items.length" class="py-6 text-center text-sm text-ink-soft">Noch keine Positionen.</p>
        </div>

        <div class="card ml-auto max-w-md p-6">
          <div class="space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-ink-soft">Gesamtbetrag netto</span><span class="tabular-nums">{{ eur(totals.net, form.currency) }}</span></div>
            <div v-for="g in totals.breakdown" :key="g.rate" class="flex justify-between"><span class="text-ink-soft">zzgl. USt {{ g.rate }}%</span><span class="tabular-nums">{{ eur(g.tax, form.currency) }}</span></div>
            <div class="flex justify-between border-t border-ink pt-2 font-semibold"><span>Gesamtbetrag brutto</span><span class="tabular-nums">{{ eur(totals.gross, form.currency) }}</span></div>
            <div v-if="form.paidAmount > 0" class="flex justify-between pt-1 text-ink-soft"><span>bereits bezahlt</span><span class="tabular-nums">−{{ eur(form.paidAmount, form.currency) }}</span></div>
            <div v-if="form.paidAmount > 0" class="flex justify-between font-semibold"><span>Offener Betrag</span><span class="tabular-nums">{{ eur(form.openAmount, form.currency) }}</span></div>
          </div>
        </div>
      </div>
    </div>

    <Modal v-if="showPay" title="Zahlung erfassen" @close="showPay = false">
      <div class="grid grid-cols-2 gap-4">
        <div><label class="label">Betrag (EUR)</label><input v-model="pay.amount" class="input text-right tabular-nums" /></div>
        <div><label class="label">Datum</label><input v-model="pay.paidAt" type="date" class="input" /></div>
        <div><label class="label">Methode</label><input v-model="pay.method" class="input" /></div>
        <div><label class="label">Referenz</label><input v-model="pay.reference" class="input" /></div>
      </div>
      <template #actions>
        <button class="btn-secondary" @click="showPay = false">Abbrechen</button>
        <button class="btn-primary" @click="savePayment">Speichern</button>
      </template>
    </Modal>
  </AppShell>
</template>
