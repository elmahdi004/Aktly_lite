<!-- resources/js/components/PaySuccess.vue -->
<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import confetti from 'canvas-confetti'
import api from '@/axios'
import { useAuthStore } from '@/pinia'

const route = useRoute()
const authStore = useAuthStore()
const verifying = ref(true)
const verifyError = ref('')

onMounted(async () => {
  const sessionId = route.query.session_id

  if (sessionId) {
    try {
      await api.get('/billing/checkout/success', {
        params: { session_id: sessionId },
      })
      await authStore.fetchUser()
    } catch (e) {
      verifyError.value = e?.response?.data?.error || 'Payment verification failed'
    } finally {
      verifying.value = false
    }
  } else {
    verifying.value = false
  }

  // Enhanced confetti effect
  const duration = 3000
  const animationEnd = Date.now() + duration
  const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 }

  function randomInRange(min, max) {
    return Math.random() * (max - min) + min
  }

  const interval = setInterval(function() {
    const timeLeft = animationEnd - Date.now()

    if (timeLeft <= 0) {
      return clearInterval(interval)
    }

    const particleCount = 50 * (timeLeft / duration)
    
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
    })
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
    })
  }, 250)
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center  px-4">
    <div class="max-w-md w-full">
      <!-- Success Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 text-center transform transition-all duration-300 hover:scale-105">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6 animate-bounce">
          <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>

        <!-- Title -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
          Paiement réussi ! 🎉
        </h1>

        <!-- Subtitle -->
        <p class="text-lg text-gray-600 mb-2">
          Votre paiement a été traité avec succès
        </p>
        <p v-if="verifying" class="text-sm text-indigo-600 mb-2">
          Vérification du paiement en cours...
        </p>
        <p v-if="verifyError" class="text-sm text-red-600 mb-2">
          {{ verifyError }}
        </p>
        <p class="text-base text-gray-500 mb-8">
          Votre abonnement est désormais actif
        </p>

        <!-- Success Details Card -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-8 border border-green-100">
          <div class="flex items-center justify-center space-x-2 text-green-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-semibold">Abonnement activé</span>
          </div>
        </div>

        <!-- Action Button -->
        <router-link 
          to="/client"
          class="inline-flex items-center justify-center w-full md:w-auto px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 hover:from-indigo-700 hover:to-purple-700"
        >
          <span>Aller au tableau de bord</span>
          <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
          </svg>
        </router-link>

        <!-- Additional Info -->
        <p class="mt-6 text-sm text-gray-500">
          Un email de confirmation vous a été envoyé
        </p>
      </div>

      <!-- Decorative Elements -->
      <div class="mt-8 text-center">
        <div class="inline-flex items-center space-x-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
          <span class="text-xs">Paiement sécurisé</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 1s ease-in-out infinite;
}
</style>
