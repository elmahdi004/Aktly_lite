<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Success Card -->
      <div
        v-if="!loading && isSuccess"
        class="bg-white rounded-2xl shadow-2xl p-8 text-center animate-fade-in"
      >
        <!-- Success Icon -->
        <div class="mb-6 flex justify-center">
          <div class="relative w-20 h-20">
            <div
              class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center animate-pulse"
            >
              <svg
                class="w-10 h-10 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                ></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ $t("subscription.successTitle") }}
        </h1>
        <p class="text-gray-600 mb-6">
          {{ $t("subscription.successMessage") }}
        </p>

        <!-- Plan Details -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 mb-6">
          <p class="text-sm text-gray-700 mb-2">{{ $t("subscription.yourPlan") }}</p>
          <p class="text-2xl font-bold text-emerald-700 capitalize">
            {{ planName }}
          </p>
          <p class="text-sm text-gray-600 mt-2">
            {{ quotaText }}
          </p>
        </div>

        <!-- Benefits -->
        <div class="space-y-3 mb-8 text-left">
          <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="text-gray-700">{{ $t("subscription.benefit1") }}</span>
          </div>
          <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="text-gray-700">{{ $t("subscription.benefit2") }}</span>
          </div>
          <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="text-gray-700">{{ $t("subscription.benefit3") }}</span>
          </div>
        </div>

        <!-- CTA Buttons -->
        <div class="space-y-3">
          <button
            @click="goToDashboard"
            class="w-full bg-gradient-to-r from-emerald-600 to-green-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200"
          >
            {{ $t("subscription.goToDashboard") }}
          </button>
          <button
            @click="goHome"
            class="w-full border-2 border-gray-300 text-gray-700 font-semibold py-3 rounded-xl hover:bg-gray-50 transition-colors"
          >
            {{ $t("subscription.backHome") || "Retour à l'accueil" }}
          </button>
        </div>

        <!-- Support -->
        <p class="text-sm text-gray-500 mt-6">
          {{ $t("subscription.needHelp") }}
          <a href="/contact" class="text-emerald-600 hover:text-emerald-700 font-semibold">
            {{ $t("subscription.contactSupport") }}
          </a>
        </p>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="flex justify-center mb-4">
          <div class="relative w-16 h-16">
            <div
              class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600 rounded-full animate-spin"
              style="mask-image: conic-gradient(transparent 70%, black)"
            ></div>
          </div>
        </div>
        <p class="text-gray-700 font-semibold">
          {{ $t("subscription.processingPayment") }}
        </p>
        <p class="text-sm text-gray-500 mt-2">
          {{ $t("subscription.pleaseWait") }}
        </p>
      </div>

      <!-- Error State -->
      <div v-else class="bg-white rounded-2xl shadow-2xl p-8 text-center">
        <div class="mb-6 flex justify-center">
          <div class="relative w-20 h-20">
            <div class="absolute inset-0 bg-red-100 rounded-full flex items-center justify-center">
              <svg
                class="w-10 h-10 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </div>
          </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ $t("subscription.errorTitle") }}
        </h1>
        <p class="text-gray-600 mb-6">
          {{ error || $t("subscription.errorMessage") }}
        </p>

        <div class="space-y-3">
          <button
            @click="retryCheckout"
            class="w-full bg-blue-600 text-white font-semibold py-3 rounded-xl hover:bg-blue-700 transition-colors"
          >
            {{ $t("subscription.retryCheckout") }}
          </button>
          <button
            @click="goToPlans"
            class="w-full border-2 border-gray-300 text-gray-700 font-semibold py-3 rounded-xl hover:bg-gray-50 transition-colors"
          >
            {{ $t("subscription.selectAnotherPlan") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "../pinia";
import axiosInstance from "../axios";

const router = useRouter();
const route = useRoute();
const { t } = useI18n();
const authStore = useAuthStore();
const loading = ref(true);
const isSuccess = ref(false);
const error = ref(null);

const sessionId = computed(() => route.query.session_id);
const planName = computed(() => {
  const plan = authStore.subscription_plan || "starter";
  return plan.charAt(0).toUpperCase() + plan.slice(1);
});

const quotaText = computed(() => {
  // Get the plan quota from subscription data
  const planQuota = authStore.plan_quota || 3;
  return `${planQuota} ${t("subscription.dossiersIncluded")}`;
});

onMounted(async () => {
  // If no session ID, redirect to buy-credits
  if (!sessionId.value) {
    error.value = "No session ID provided";
    loading.value = false;
    return;
  }

  try {
    // Verify the checkout session with backend
    const response = await axiosInstance.get("/billing/checkout/success", {
      params: { session_id: sessionId.value },
      timeout: 15000,
    });

    // Update auth store with new subscription info
    const subscriptionData = await axiosInstance.get("/billing/subscription", {
      timeout: 15000,
    });
    authStore.subscription_status = subscriptionData.data.status;
    authStore.subscription_plan = subscriptionData.data.plan;
    authStore.plan_quota = subscriptionData.data.plan_quota;
    authStore.cumulative_dossier_count = subscriptionData.data.dossiers_created;

    isSuccess.value = true;
  } catch (err) {
    error.value = err.response?.data?.error || "Subscription verification failed";
  } finally {
    loading.value = false;
  }
});

const goToDashboard = () => {
  router.push("/dashboard");
};

const goHome = () => {
  router.push("/");
};

const goToPlans = () => {
  router.push({ name: "buyCredits", query: { msg: "subscription_failed" } });
};

const retryCheckout = () => {
  router.push({ name: "buyCredits" });
};
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fade-in 0.5s ease-out;
}
</style>
