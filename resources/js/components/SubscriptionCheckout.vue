<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $t('billing.checkoutTitle') }}</h1>
      <p class="text-gray-600 mb-6">{{ $t('billing.checkoutSubtitle') }}</p>

      <div id="payment-element" class="mb-6"></div>

      <button
        @click="confirmPayment"
        :disabled="loading || !ready"
        class="w-full py-3 rounded-xl bg-indigo-600 text-white font-semibold disabled:bg-gray-400"
      >
        <span v-if="loading">{{ $t('billing.loading') }}</span>
        <span v-else>{{ $t('billing.payNow') }}</span>
      </button>

      <p v-if="error" class="text-sm text-red-600 mt-4">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { loadStripe } from '@stripe/stripe-js'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const ready = ref(false)
const error = ref('')

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY)
let stripe = null
let elements = null

onMounted(async () => {
  const clientSecret = route.query.cs
  if (!clientSecret) {
    router.push('/buy-credits')
    return
  }

  stripe = await stripePromise
  if (!stripe) {
    error.value = 'Stripe initialization failed'
    return
  }

  elements = stripe.elements({ clientSecret })
  const paymentElement = elements.create('payment')
  paymentElement.mount('#payment-element')
  ready.value = true
})

const confirmPayment = async () => {
  if (!stripe || !elements) return
  loading.value = true
  error.value = ''

  const { error: stripeError } = await stripe.confirmPayment({
    elements,
    confirmParams: {
      return_url: `${window.location.origin}/buy-credits/success`,
    },
    redirect: 'if_required',
  })

  if (stripeError) {
    error.value = stripeError.message || 'Payment failed'
    loading.value = false
    return
  }

  loading.value = false
  router.push('/buy-credits/success')
}
</script>
