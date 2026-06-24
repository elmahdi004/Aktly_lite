<script setup>
import Pricing from '@/components/Pricing.vue'
import { inject, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/pinia.js'

const updateStep = inject('updateStep')
const router = useRouter()
const auth = useAuthStore()
const pollInterval = ref(null)

const checkCreditsStatus = async () => {
    try {
        await auth.fetchUser()
        // Creating a demande costs 5 credits, so user needs at least 5 credits
        if (auth.user?.credits >= 5) {
            clearInterval(pollInterval.value)
            // Redirect to enterprise info step
            router.push({ name: 'enterpriseInfo' })
        }
    } catch (error) {
        console.error('Error checking credits:', error)
    }
}

onMounted(() => {
    if (updateStep) updateStep(1) // This is step 1
    
    // If user already has enough credits, redirect
    // Creating a demande costs 5 credits, so user needs at least 5 credits
    if (auth.user?.credits >= 5) {
        router.push({ name: 'enterpriseInfo' })
    } else {
        // Start polling for credit updates every 3 seconds
        pollInterval.value = setInterval(checkCreditsStatus, 3000)
    }
})

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value)
    }
})
</script>

<template>
  <div class="w-[60%]">
    <div class="titles text-center mt-4">
        <h1 class="text-2xl font-bold">Buy Credits</h1>
    </div>
    <p class="text-[#64748b] text-sm mt-4 text-center">
        You need at least 5 credits to create a demande.
    </p>
    <Pricing />
  </div>
</template>