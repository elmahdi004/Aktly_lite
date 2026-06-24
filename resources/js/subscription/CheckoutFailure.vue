<template>
  <div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Error Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8 text-center animate-fade-in">
        <!-- Error Icon -->
        <div class="mb-6 flex justify-center">
          <div class="relative w-20 h-20">
            <div
              class="absolute inset-0 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center"
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
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ $t("subscription.failureTitle") }}
        </h1>
        <p class="text-gray-600 mb-6">
          {{ $t("subscription.failureMessage") }}
        </p>

        <!-- Error Details -->
        <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6 text-left">
          <p class="text-sm text-red-700">
            <span class="font-semibold">{{ $t("subscription.errorDetails") }}:</span>
          </p>
          <p class="text-sm text-red-600 mt-1">
            {{ reason || $t("subscription.unknownError") }}
          </p>
        </div>

        <!-- What went wrong -->
        <div class="bg-orange-50 rounded-lg p-4 mb-6 text-left">
          <p class="text-sm font-semibold text-gray-900 mb-3">
            {{ $t("subscription.possibleReasons") }}:
          </p>
          <ul class="space-y-2 text-sm text-gray-700">
            <li class="flex items-start">
              <svg class="w-4 h-4 text-orange-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <span>{{ $t("subscription.reason1") }}</span>
            </li>
            <li class="flex items-start">
              <svg class="w-4 h-4 text-orange-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <span>{{ $t("subscription.reason2") }}</span>
            </li>
            <li class="flex items-start">
              <svg class="w-4 h-4 text-orange-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <span>{{ $t("subscription.reason3") }}</span>
            </li>
          </ul>
        </div>

        <!-- CTA Buttons -->
        <div class="space-y-3">
          <button
            @click="retryCheckout"
            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transform hover:scale-105 transition-all duration-200"
          >
            {{ $t("subscription.tryAgain") }}
          </button>
          <button
            @click="contactSupport"
            class="w-full border-2 border-blue-600 text-blue-600 font-semibold py-3 rounded-xl hover:bg-blue-50 transition-colors"
          >
            {{ $t("subscription.contactSupport") }}
          </button>
          <button
            @click="goHome"
            class="w-full border-2 border-gray-300 text-gray-700 font-semibold py-3 rounded-xl hover:bg-gray-50 transition-colors"
          >
            {{ $t("subscription.backHome") || "Retour à l'accueil" }}
          </button>
        </div>

        <!-- Support Info -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <p class="text-xs text-gray-500 mb-3">{{ $t("subscription.needHelp") }}</p>
          <div class="flex items-center justify-center space-x-4 text-sm">
            <a href="mailto:support@legakte.be" class="text-blue-600 hover:text-blue-700 font-semibold">
              support@legakte.be
            </a>
            <span class="text-gray-300">•</span>
            <a href="/help" class="text-blue-600 hover:text-blue-700 font-semibold">
              {{ $t("subscription.faq") }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();

const reason = computed(() => {
  const reasonMap = {
    payment_declined: "$t('subscription.paymentDeclined')",
    expired_card: "$t('subscription.expiredCard')",
    insufficient_funds: "$t('subscription.insufficientFunds')",
    user_cancelled: "$t('subscription.userCancelled')",
  };

  return reasonMap[route.query.reason] || route.query.reason || null;
});

const retryCheckout = () => {
  router.push({ name: "buyCredits", query: { msg: "retry_checkout" } });
};

const goHome = () => {
  router.push("/");
};

const contactSupport = () => {
  window.location.href = "mailto:support@legakte.be?subject=Subscription Payment Failed";
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
