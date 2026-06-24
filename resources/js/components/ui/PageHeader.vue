<!-- PageHeader.vue -->
<script setup>
/* Props let you re-use it anywhere */
import { useI18n } from "vue-i18n";
const { t } = useI18n();

defineProps({
  title:  { type: String, required: true },        // e.g. 'Mon profil'
  homeTo: { type: String, default: '/' },          // route name or path
})
</script>

<template>
  <header class="w-full bg-white/80 backdrop-blur ring-1 ring-gray-200">
    <div class="max-w-6xl mx-auto flex items-center gap-4 px-4 sm:px-6 lg:px-8 py-4">

      <!-- Breadcrumb back to home -->
      <router-link :to="homeTo"
                   class="flex items-center text-indigo-600 hover:text-indigo-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M15 19l-7-7 7-7" />
        </svg>
        <span class="ml-1 text-sm">{{ $t('profile.home') }}</span>
      </router-link>

      <!-- Divider dot -->
      <span class="text-gray-300 mx-2">•</span>

      <!-- Page title -->
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">
        {{ title }}
      </h1>
    </div>
  </header>
</template>
