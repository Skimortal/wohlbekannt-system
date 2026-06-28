<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppShell from '../components/AppShell.vue'
import api from '../api'

const form = ref<any>(null)
const saving = ref(false)
const saved = ref(false)

onMounted(async () => {
  const { data } = await api.get('/api/company-settings')
  form.value = data
})

async function save() {
  saving.value = true
  saved.value = false
  try {
    const { data } = await api.put('/api/company-settings', form.value)
    form.value = data
    saved.value = true
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppShell>
    <template #breadcrumb>Einstellungen</template>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-ink">Einstellungen</h1>

    <div v-if="form" class="max-w-3xl space-y-6">
      <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-ink">Firma</h2>
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2"><label class="label">Firmenname</label><input v-model="form.legalName" class="input" /></div>
          <div class="col-span-2"><label class="label">Straße</label><input v-model="form.address.street" class="input" /></div>
          <div><label class="label">PLZ</label><input v-model="form.address.postalCode" class="input" /></div>
          <div><label class="label">Ort</label><input v-model="form.address.city" class="input" /></div>
          <div><label class="label">Telefon</label><input v-model="form.phone" class="input" /></div>
          <div><label class="label">E-Mail</label><input v-model="form.email" class="input" /></div>
          <div><label class="label">Web</label><input v-model="form.web" class="input" /></div>
          <div><label class="label">Geschäftsführung</label><input v-model="form.managingDirector" class="input" /></div>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-ink">Steuer & Bank</h2>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">FN-Nr.</label><input v-model="form.companyRegisterNumber" class="input" /></div>
          <div><label class="label">USt-ID</label><input v-model="form.vatId" class="input" /></div>
          <div><label class="label">Steuer-Nr.</label><input v-model="form.taxNumber" class="input" /></div>
          <div><label class="label">Bank</label><input v-model="form.bankName" class="input" /></div>
          <div><label class="label">IBAN</label><input v-model="form.iban" class="input" /></div>
          <div><label class="label">BIC</label><input v-model="form.bic" class="input" /></div>
        </div>
      </div>

      <div class="card p-6">
        <h2 class="mb-4 text-base font-semibold text-ink">Vorgaben</h2>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="label">Standard-USt (%)</label><input v-model="form.defaultVatRate" class="input" /></div>
          <div><label class="label">Zahlungsziel (Tage)</label><input v-model.number="form.defaultPaymentTermsDays" type="number" class="input" /></div>
          <div><label class="label">Angebot gültig (Tage)</label><input v-model.number="form.defaultQuoteValidityDays" type="number" class="input" /></div>
          <div class="col-span-2"><label class="label">Angebot – Einleitungstext</label><textarea v-model="form.quoteIntroText" rows="3" class="input"></textarea></div>
          <div class="col-span-2"><label class="label">Angebot – Schlusstext</label><textarea v-model="form.quoteOutroText" rows="3" class="input"></textarea></div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Speichern…' : 'Speichern' }}</button>
        <span v-if="saved" class="text-sm text-green-700">Gespeichert.</span>
      </div>
    </div>
  </AppShell>
</template>
