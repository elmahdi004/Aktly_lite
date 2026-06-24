<!-- InvoiceList.vue ------------------------------------------------- -->
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'
import { useI18n } from "vue-i18n";
 
const loading  = ref(true)
const error    = ref(null)
const invoices = ref([])
const receipts = ref([])
const { t } = useI18n();

onMounted(async () => {
  try {
    // Try to fetch invoices first
    const { data: invoiceData } = await api.get('/invoices')
    invoices.value = invoiceData
    console.log('Fetched invoices:', invoices);

    // If no invoices, try receipts as fallback
    if (invoiceData.length === 0) {
      const { data: receiptData } = await api.get('/receipts')
      receipts.value = receiptData
      console.log('Fetched receipts as fallback:', receiptData);
    }
  } catch (e) {
      error.value = e.response?.data?.message ?? t('profile.nofactures')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <!-- Loading / error -->
  <div v-if="loading" class="text-gray-500">Chargement…</div>
  <div v-else-if="error"  class="text-red-600">{{ error }}</div>

  <!-- No invoices and no receipts -->
  <div v-else-if="invoices.length === 0 && receipts.length === 0"
       class="text-gray-500 italic">
    Aucune facture pour l'instant.
  </div>

  <!-- Invoice/Receipt table -->
  <div v-else class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-gray-500 border-b">
          <th class="py-2 pr-4">N°</th>
          <th class="py-2 pr-4">Montant</th>
          <th class="py-2 pr-4">Statut</th>
          <th class="py-2 pr-4">Date</th>
          <th class="py-2 pr-4"></th>
        </tr>
      </thead>
      <tbody>
        <!-- Show invoices if available -->
        <tr v-for="inv in invoices" :key="inv.id" class="border-b last:border-0">
          <td class="py-2 pr-4">{{ inv.number }}</td>
          <td class="py-2 pr-4">€{{ (inv.amount_total || 0).toFixed(2) }}</td>
          <td class="py-2 pr-4 capitalize">{{ inv.status }}</td>
          <td class="py-2 pr-4">
            {{ new Date(inv.created_at * 1000).toLocaleDateString() }}
          </td>
          <td class="py-2 pr-0">
            <a :href="inv.pdf" target="_blank" class="text-indigo-600 hover:underline
                       inline-flex items-center gap-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor"
                   viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 5v14m7-7H5" />
              </svg>
              Télécharger PDF
            </a>
          </td>
        </tr>
        
        <!-- Show receipts as fallback -->
        <tr v-for="rec in receipts" :key="rec.id" class="border-b last:border-0">
          <td class="py-2 pr-4">Reçu</td>
          <td class="py-2 pr-4">€{{ rec.amount.toFixed(2) }}</td>
          <td class="py-2 pr-4 capitalize">{{ rec.status }}</td>
          <td class="py-2 pr-4">
            {{ new Date(rec.created_at * 1000).toLocaleDateString() }}
          </td>
          <td class="py-2 pr-0">
            <a :href="rec.receipt_url" target="_blank" class="text-indigo-600 hover:underline
                       inline-flex items-center gap-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor"
                   viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 5v14m7-7H5" />
              </svg>
              Voir reçu
            </a>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
