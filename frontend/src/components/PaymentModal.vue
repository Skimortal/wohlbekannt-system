<script setup lang="ts">
import { reactive, ref } from 'vue'
import Modal from './Modal.vue'
import api from '../api'
import { centsToInput, eur, toCents } from '../lib/format'

/** Minimal invoice shape needed to record a payment (list item or full invoice). */
interface PayableInvoice {
  id: number
  number: string | null
  currency: string
  totalGross: number
  paidAmount: number
  openAmount: number
}

const props = defineProps<{ invoice: PayableInvoice }>()
const emit = defineEmits<{ close: []; saved: [invoice: any] }>()

const open = Math.max(0, props.invoice.openAmount)
const pay = reactive({
  // Prefill with what is still owed (= full invoice amount if nothing was paid yet).
  amount: centsToInput(open > 0 ? open : props.invoice.totalGross),
  method: 'Überweisung',
  reference: '',
  paidAt: new Date().toISOString().slice(0, 10),
})
const saving = ref(false)
const error = ref('')

async function save() {
  error.value = ''
  if (toCents(pay.amount) <= 0) {
    error.value = 'Bitte einen Betrag größer 0 angeben.'
    return
  }
  saving.value = true
  try {
    const { data } = await api.post(`/api/invoices/${props.invoice.id}/payments`, {
      amount: toCents(pay.amount), method: pay.method, reference: pay.reference, paidAt: pay.paidAt,
    })
    emit('saved', data)
  } catch (e: any) {
    error.value = e?.response?.data?.error || 'Zahlung konnte nicht gespeichert werden.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal :title="`Zahlung erfassen${invoice.number ? ' – ' + invoice.number : ''}`" @close="emit('close')">
    <div class="mb-4 grid grid-cols-3 gap-3 rounded-xl bg-sand-50 p-3 text-sm">
      <div><div class="text-xs text-ink-soft">Rechnungsbetrag</div><div class="tabular-nums font-medium">{{ eur(invoice.totalGross, invoice.currency) }}</div></div>
      <div><div class="text-xs text-ink-soft">bereits bezahlt</div><div class="tabular-nums font-medium">{{ eur(invoice.paidAmount, invoice.currency) }}</div></div>
      <div><div class="text-xs text-ink-soft">noch offen</div><div class="tabular-nums font-semibold">{{ eur(open, invoice.currency) }}</div></div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><label class="label">Betrag ({{ invoice.currency }})</label><input v-model="pay.amount" class="input text-right tabular-nums" /></div>
      <div><label class="label">Datum</label><input v-model="pay.paidAt" type="date" class="input" /></div>
      <div><label class="label">Methode</label><input v-model="pay.method" class="input" /></div>
      <div><label class="label">Referenz</label><input v-model="pay.reference" class="input" /></div>
    </div>
    <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
    <template #actions>
      <button class="btn-secondary" @click="emit('close')">Abbrechen</button>
      <button class="btn-primary" :disabled="saving" @click="save">Speichern</button>
    </template>
  </Modal>
</template>
