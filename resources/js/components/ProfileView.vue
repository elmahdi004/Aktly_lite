<script setup>
import { ref, onMounted } from 'vue'
import api from '@/axios'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/ui/PageHeader.vue'
import InvoiceList from '@/components/InvoiceList.vue'
import { useI18n } from "vue-i18n";
import {
  User,
  Mail,
  Globe,
  Building2,
  Hash,
  CreditCard,
  Sparkles,
  ArrowUpRight,
  Receipt,
  KeyRound,
  ChevronRight,
  AlertCircle,
} from "lucide-vue-next";

const router = useRouter()
const loading = ref(true)
const error = ref(null)
const profile = ref({})
const { t } = useI18n();

onMounted(async () => {
  try {
    const { data } = await api.get('/profile')
    profile.value = data
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Erreur de chargement'
  } finally {
    loading.value = false
  }
})


function toManageSubscription() {
  router.push('/buy-credits')
}
</script>

<template>
  <div class="aurora-wrap relative min-h-screen w-full bg-slate-50">
    <!-- soft aurora accents -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="aurora-blob" style="width:440px;height:440px;background:#a7f3d0;top:-180px;right:-90px;opacity:.20;"></div>
      <div class="aurora-blob" style="width:360px;height:360px;background:#6ee7b7;bottom:-200px;left:-120px;opacity:.14;"></div>
    </div>

    <PageHeader :title="$t('profile.title')" />

    <div class="relative z-10 mx-auto max-w-4xl px-4 py-10 sm:px-6">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-28">
        <span class="h-9 w-9 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="animate-fade-up mx-auto max-w-md">
        <div class="flex items-center gap-3 rounded-2xl border border-rose-100 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-600">
          <AlertCircle class="h-5 w-5 shrink-0" />
          {{ error }}
        </div>
      </div>

      <!-- Profile -->
      <div v-else class="space-y-7">

        <!-- ===== Identity hero ===== -->
        <section class="glass-card animate-fade-up flex flex-col items-center gap-5 p-7 sm:flex-row sm:items-center sm:gap-6 sm:p-8">
          <div class="relative shrink-0">
            <div class="brand-gradient grid h-20 w-20 place-items-center rounded-3xl font-display text-2xl font-extrabold text-white shadow-lg shadow-emerald-600/30">
              {{ profile.name?.charAt(0)?.toUpperCase() ?? '?' }}
            </div>
            <span class="absolute -bottom-1 -right-1 grid h-6 w-6 place-items-center rounded-full border-2 border-white bg-emerald-500">
              <span class="h-2 w-2 rounded-full bg-white"></span>
            </span>
          </div>
          <div class="min-w-0 text-center sm:text-left">
            <h2 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">
              {{ profile.name || '—' }}
            </h2>
            <p class="mt-1 flex items-center justify-center gap-1.5 text-sm text-slate-500 sm:justify-start">
              <Mail class="h-3.5 w-3.5 shrink-0 text-slate-400" />
              <span class="truncate">{{ profile.email || '—' }}</span>
            </p>
          </div>
        </section>

        <!-- ===== Account information ===== -->
        <section class="glass-card animate-fade-up-1 p-6 sm:p-8">
          <div class="mb-6 flex items-start gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
              <User class="h-5 w-5" />
            </div>
            <div>
              <h3 class="font-display text-lg font-extrabold tracking-tight text-slate-900">
                {{ $t('profile.title') }}
              </h3>
              <p class="mt-0.5 text-sm text-slate-500">{{ $t('profile.manageInfo') }}</p>
            </div>
          </div>

          <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 sm:grid-cols-2">
            <!-- Name -->
            <div class="flex items-start gap-3 bg-white px-4 py-4">
              <User class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-400">{{ $t("profile.fullName") }}</dt>
                <dd class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ profile.name || '—' }}</dd>
              </div>
            </div>

            <!-- Email -->
            <div class="flex items-start gap-3 bg-white px-4 py-4">
              <Mail class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-400">{{ $t("profile.email") }}</dt>
                <dd class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ profile.email || '—' }}</dd>
              </div>
            </div>

            <!-- Institution -->
            <div class="flex items-start gap-3 bg-white px-4 py-4">
              <Building2 class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-400">{{ $t("register.institution") }}</dt>
                <dd class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ profile.institution || '—' }}</dd>
              </div>
            </div>

            <!-- Member number -->
            <div class="flex items-start gap-3 bg-white px-4 py-4">
              <Hash class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-400">{{ $t("register.memberNumber") }}</dt>
                <dd class="mt-0.5 truncate font-mono text-sm font-semibold text-slate-800">{{ profile.member_number || '—' }}</dd>
              </div>
            </div>

            <!-- Preferred language -->
            <div class="flex items-start gap-3 bg-white px-4 py-4 sm:col-span-2">
              <Globe class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div class="min-w-0">
                <dt class="text-xs font-medium text-slate-400">{{ $t('profile.preferredLang') }}</dt>
                <dd class="mt-0.5">
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold uppercase text-emerald-700">
                    {{ profile.langue_user || '—' }}
                  </span>
                </dd>
              </div>
            </div>
          </dl>
        </section>

        <!-- ===== Plan & credits ===== -->
        <section class="glass-card animate-fade-up-2 p-6 sm:p-8">
          <div class="mb-6 flex items-center gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
              <CreditCard class="h-5 w-5" />
            </div>
            <h3 class="font-display text-lg font-extrabold tracking-tight text-slate-900">
              {{ $t('profile.subscriptionPlan') }}
            </h3>
          </div>

          <div class="flex flex-col gap-5 rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
              <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-emerald-600 shadow-sm">
                <Sparkles class="h-5 w-5" />
              </div>
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">{{ $t('profile.subscriptionPlan') }}</p>
                <p class="font-display text-xl font-extrabold tracking-tight text-slate-900">
                  {{ profile.subscription_plan?.toUpperCase() || $t('profile.noSubscription') }}
                </p>
                <p class="mt-1 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                  {{ $t('profile.subscriptionStatus') }}: {{ profile.subscription_status || '-' }}
                </p>
              </div>
            </div>

            <button
              @click="toManageSubscription"
              class="btn-aurora w-full shrink-0 sm:w-auto"
            >
              <span>{{ $t('profile.manageSubscription') }}</span>
              <ArrowUpRight class="h-4 w-4" />
            </button>
          </div>

          <!-- Change password link -->
          <router-link
            :to="{ name: 'changePassword' }"
            class="group mt-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-emerald-300 hover:bg-emerald-50/40"
          >
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500 transition group-hover:bg-emerald-100 group-hover:text-emerald-700">
              <KeyRound class="h-4 w-4" />
            </span>
            <span class="flex-1 text-sm font-semibold text-slate-700">Modifier le mot de passe</span>
            <ChevronRight class="h-4 w-4 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-emerald-600" />
          </router-link>
        </section>

        <!-- ===== Payments / invoices ===== -->
        <section class="glass-card animate-fade-up-3 p-6 sm:p-8">
          <div class="mb-6 flex items-center gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
              <Receipt class="h-5 w-5" />
            </div>
            <h3 class="font-display text-lg font-extrabold tracking-tight text-slate-900">
              {{ $t('profile.invoicesTitle') }}
            </h3>
          </div>

          <!-- fetch invoices once profile is loaded -->
          <Suspense>
            <template #default>
              <InvoiceList />
            </template>
            <template #fallback>
              <div class="flex items-center gap-2 py-4 text-sm text-slate-400">
                <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
                {{ $t('profile.invoiceLoading') }}
              </div>
            </template>
          </Suspense>
        </section>

      </div>
    </div>
  </div>
</template>
