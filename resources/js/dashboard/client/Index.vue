<template>
    <div class="aurora-wrap relative min-h-screen w-full bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:440px;height:440px;background:#a7f3d0;top:-200px;right:-120px;opacity:.18;"></div>
            <div class="aurora-blob" style="width:380px;height:380px;background:#6ee7b7;bottom:-220px;left:-140px;opacity:.14;"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl">
            <!-- ============ Page header ============ -->
            <header class="animate-fade-up flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                        {{ $t('dashboard.title') || 'Tableau de bord' }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $t('dashboard.subtitle') || 'Suivez vos dossiers et reprenez vos démarches en un clin d’œil.' }}
                    </p>
                </div>
                <button type="button" @click="goToNewDemande" class="btn-aurora shrink-0">
                    <Plus class="h-4 w-4" />
                    <span>{{ $t('dashboard.new_request') || 'Nouvelle demande' }}</span>
                </button>
            </header>

            <!-- ============ Stat cards ============ -->
            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(stat, index) in stats"
                    :key="index"
                    class="glass-card animate-fade-up-1 flex items-center gap-4 p-5"
                >
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <component :is="statIcons[index] || FileText" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-medium text-slate-500">{{ $t(stat.title) }}</h3>
                        <span class="font-display text-2xl font-extrabold tracking-tight text-slate-900">{{ stat.count }}</span>
                    </div>
                </div>
            </div>

            <!-- ============ Demandes In Progress ============ -->
            <section v-if="demandesInProgress.length > 0" class="animate-fade-up-2 mt-10">
                <h2 class="font-display mb-4 flex items-center gap-2 text-xl font-extrabold tracking-tight text-slate-900">
                    <Loader class="h-5 w-5 text-emerald-600" />
                    {{ $t('dashboard.demandes_in_progress') || 'Demandes en cours' }}
                </h2>
                <div class="grid gap-5 md:grid-cols-2">
                    <div
                        v-for="demande in demandesInProgress"
                        :key="demande.id"
                        class="glass-card flex flex-col gap-4 p-6"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-semibold text-emerald-700">#{{ demande.id }}</span>
                            <span class="text-xs text-slate-400">{{ formatDateTime(demande.created_at) }}</span>
                            <span class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                                <Clock class="h-3.5 w-3.5" />
                                {{ $t('dashboard.in_progress') || 'En cours' }}
                            </span>
                        </div>

                        <dl class="flex flex-col gap-1.5 text-sm">
                            <div class="flex gap-2">
                                <dt class="font-medium text-slate-500">{{ $t('dashboard.numero_entreprise') }}:</dt>
                                <dd class="font-mono text-slate-800">{{ demande.enterprise_number }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="font-medium text-slate-500">{{ $t('dashboard.langue') }}:</dt>
                                <dd class="text-slate-800">{{ demande.langue_entreprise }}</dd>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="font-medium text-slate-500">{{ $t('dashboard.services') }}:</dt>
                                <span v-if="demande.address_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    <MapPin class="h-3 w-3" /> {{ $t('dashboard.adresse') }}
                                </span>
                                <span v-if="demande.dirigeants_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    <Users class="h-3 w-3" /> {{ $t('dashboard.dirigeants') }}
                                </span>
                                <span v-if="demande.cession_parts_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                    <FileText class="h-3 w-3" /> {{ $t('dashboard.cession_parts') }}
                                </span>
                            </div>
                        </dl>

                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">{{ $t('dashboard.progress') || 'Progression' }}</span>
                                <span class="ml-auto text-xs font-semibold text-emerald-700">{{ demande.progress || 0 }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-500" :style="{ width: (demande.progress || 0) + '%' }"></div>
                            </div>
                        </div>

                        <div class="mt-1 flex items-center gap-2">
                            <button @click="resumeDemande(demande.id)" class="btn-aurora flex-1 !py-2.5">
                                {{ $t('dashboard.resume') || 'Finaliser' }}
                                <ArrowRight class="h-4 w-4" />
                            </button>
                            <button @click="openDeleteModal(demande)" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-100">
                                <Trash2 class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ $t('dashboard.delete') || 'Supprimer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ Filters ============ -->
            <div class="glass-card animate-fade-up-2 mt-10 p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <!-- Search Input -->
                    <div class="min-w-0 flex-1">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('dashboard.search') || $t('address.search_by_id_or_number') }}</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="input-aurora pl-11 pr-10"
                                :placeholder="$t('address.search_by_id_or_number')"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Filter Controls -->
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <!-- Language Filter -->
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('dashboard.language') }}</label>
                            <div class="relative">
                                <Globe class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                                <select
                                    v-model="selectedLang"
                                    class="input-aurora cursor-pointer appearance-none pl-11 pr-9 sm:min-w-[150px]"
                                >
                                    <option value="">{{ $t('dashboard.all_languages') }}</option>
                                    <option value="fr">🇫🇷 Français</option>
                                    <option value="nl">🇳🇱 Nederlands</option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            </div>
                        </div>

                        <!-- Service Filter -->
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ $t('dashboard.service') }}</label>
                            <div class="relative">
                                <Filter class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                                <select
                                    v-model="selectedService"
                                    class="input-aurora cursor-pointer appearance-none pl-11 pr-9 sm:min-w-[170px]"
                                >
                                    <option value="">{{ $t('dashboard.all_services') }}</option>
                                    <option value="address">{{ $t('dashboard.adresse') }}</option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            </div>
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="flex items-end">
                            <button
                                v-if="searchQuery || selectedLang || selectedService"
                                @click="clearAllFilters"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                            >
                                <RotateCcw class="h-4 w-4" />
                                {{ $t('dashboard.clear_filters') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Display -->
                <div v-if="hasActiveFilters" class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-slate-500">{{ $t('dashboard.active_filters') }}:</span>

                        <span v-if="searchQuery" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm text-emerald-700">
                            <Search class="h-3 w-3" />
                            "{{ searchQuery }}"
                            <button @click="searchQuery = ''" class="transition hover:text-emerald-900">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <span v-if="selectedLang" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm text-emerald-700">
                            <Globe class="h-3 w-3" /> {{ selectedLang.toUpperCase() }}
                            <button @click="selectedLang = ''" class="transition hover:text-emerald-900">
                                <X class="h-3 w-3" />
                            </button>
                        </span>

                        <span v-if="selectedService" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm text-emerald-700">
                            <Filter class="h-3 w-3" /> {{ getServiceLabel(selectedService) }}
                            <button @click="selectedService = ''" class="transition hover:text-emerald-900">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============ Dossiers récents (table) ============ -->
            <section v-if="filteredRequests.length > 0" class="animate-fade-up-3 mt-10">
                <h2 class="font-display mb-4 flex items-center gap-2 text-xl font-extrabold tracking-tight text-slate-900">
                    <FolderClock class="h-5 w-5 text-emerald-600" />
                    {{ $t('dashboard.mes_demandes') }}
                </h2>

                <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-white shadow-sm">
                    <table class="w-full min-w-[640px] border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th v-for="header in translatedHeaders" :key="header" class="px-5 py-3.5">{{ header }}</th>
                                <th class="px-5 py-3.5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="request in filteredRequests"
                                :key="request.id"
                                class="transition-colors hover:bg-emerald-50/40"
                            >
                                <td class="px-5 py-4 font-mono text-slate-500">#{{ request.id }}</td>
                                <td class="px-5 py-4 font-mono text-slate-800">{{ request.enterprise_number }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                        {{ request.langue_entreprise === 'nl' ? '🇳🇱' : '🇫🇷' }} {{ request.langue_entreprise }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-if="request.address_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                            <MapPin class="h-3 w-3" /> {{ $t('dashboard.adresse') }}
                                        </span>
                                        <span v-if="request.dirigeants_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                            <Users class="h-3 w-3" /> {{ $t('dashboard.dirigeants') }}
                                        </span>
                                        <span v-if="request.cession_parts_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                            <FileText class="h-3 w-3" /> {{ $t('dashboard.cession_parts') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-slate-500">{{ formatDateTime(request.created_at) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="relative inline-block text-left">
                                        <button
                                            type="button"
                                            @click.stop="toggleMenu(request.id)"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700"
                                        >
                                            {{ $t('dashboard.consulter') || 'Consulter' }}
                                            <ChevronDown class="h-4 w-4 transition-transform" :class="openMenuId === request.id ? 'rotate-180' : ''" />
                                        </button>
                                        <transition name="fade">
                                            <div v-if="openMenuId === request.id" class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-900/5">
                                                <button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700" @click.stop="openIdModal(request.id)">
                                                    <Hash class="h-4 w-4 text-slate-400" />
                                                    {{ $t('dashboard.show_id_full') || 'Afficher ID complet' }}
                                                </button>
                                                <button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700" @click.stop="goToDemandeFiles(request.id)">
                                                    <Download class="h-4 w-4 text-slate-400" />
                                                    {{ $t('dashboard.view_or_download') || 'Voir ou Télécharger' }}
                                                </button>
                                                <button class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700" @click.stop="goToModify(request.id)">
                                                    <Pencil class="h-4 w-4 text-slate-400" />
                                                    {{ $t('dashboard.modify') || 'Modifier' }}
                                                </button>
                                            </div>
                                        </transition>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ============ Empty state ============ -->
            <div
                v-else-if="!loading && demandesInProgress.length === 0"
                class="glass-card animate-fade-up-3 mt-10 flex flex-col items-center gap-4 px-6 py-16 text-center"
            >
                <div class="grid h-16 w-16 place-items-center rounded-3xl bg-emerald-100 text-emerald-600">
                    <FolderOpen class="h-8 w-8" />
                </div>
                <div>
                    <h3 class="font-display text-lg font-extrabold text-slate-900">
                        {{ hasActiveFilters
                            ? ($t('dashboard.no_results') || 'Aucun résultat')
                            : ($t('dashboard.no_demandes') || 'Aucune demande pour le moment') }}
                    </h3>
                    <p class="mx-auto mt-1.5 max-w-sm text-sm text-slate-500">
                        {{ hasActiveFilters
                            ? ($t('dashboard.no_results_hint') || 'Essayez de modifier ou réinitialiser vos filtres.')
                            : ($t('dashboard.no_demandes_hint') || 'Démarrez votre première démarche en quelques minutes.') }}
                    </p>
                </div>
                <button v-if="hasActiveFilters" type="button" @click="clearAllFilters" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800">
                    <RotateCcw class="h-4 w-4" />
                    {{ $t('dashboard.clear_filters') }}
                </button>
                <button v-else type="button" @click="goToNewDemande" class="btn-aurora">
                    <Plus class="h-4 w-4" />
                    {{ $t('dashboard.new_request') || 'Nouvelle demande' }}
                </button>
            </div>
        </div>

        <!-- Loading Spinner Overlay -->
        <div
            v-if="loading"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 backdrop-blur-sm"
        >
            <span class="h-10 w-10 animate-spin rounded-full border-4 border-emerald-500/25 border-t-emerald-500"></span>
        </div>

        <!-- Full ID Modal -->
        <div v-if="showIdModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeIdModal"></div>
            <div class="glass-card animate-fade-up relative w-full max-w-md p-6 sm:p-8">
                <div class="mb-3 flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <Hash class="h-5 w-5" />
                    </div>
                    <h3 class="font-display text-lg font-extrabold text-slate-900">{{ $t('dashboard.id_full_title') || 'ID complet' }}</h3>
                </div>
                <p class="break-all rounded-xl bg-slate-50 px-4 py-3 font-mono text-sm text-slate-700">{{ fullIdValue }}</p>
                <div class="mt-6 flex justify-end">
                    <button @click="closeIdModal" class="btn-aurora">
                        {{ $t('dashboard.ok') || 'OK' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="cancelDelete"></div>
            <div class="glass-card animate-fade-up relative w-full max-w-md p-6 sm:p-8">
                <div class="mb-3 flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose-600">
                        <AlertTriangle class="h-5 w-5" />
                    </div>
                    <h3 class="font-display text-lg font-extrabold text-slate-900">{{ $t('dashboard.confirm_delete_title') || 'Confirmer la suppression' }}</h3>
                </div>
                <p class="text-sm text-slate-500">
                    {{ $t('dashboard.confirm_delete_text') || 'Êtes-vous sûr de vouloir supprimer cette demande ? Cette action est irréversible.' }}
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="cancelDelete" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800">
                        {{ $t('dashboard.cancel') || 'Annuler' }}
                    </button>
                    <button @click="confirmDelete" :disabled="deleting" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60">
                        <span v-if="deleting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        {{ deleting ? ($t('dashboard.deleting') || 'Suppression...') : ($t('dashboard.confirm') || 'Confirmer') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Filters and search
import { ref, onMounted, onBeforeUnmount, watch, computed  } from "vue";
// ...existing code...

const searchQuery = ref("");
const selectedLang = ref("");
const selectedService = ref("");
const loading = ref(true);

const hasActiveFilters = computed(() => {
  return !!(searchQuery.value || selectedLang.value || selectedService.value);
});

const getServiceLabel = (service) => {
  if (service === "address") return "Adresse";
  if (service === "dirigeants") return "Dirigeants";
  if (service === "cession_parts") return "Cession Parts";
  return "";
};

const demandesInProgress = computed(() => {
  return requests.value.filter(req => req.progress !== undefined && req.progress !== null && req.progress < 100);
});

const filteredRequests = computed(() => {
  return requests.value.filter((req) => {
    // Exclude demandes in progress from the main table
    if (req.progress !== undefined && req.progress !== null && req.progress < 100) return false;
    // Search by id or enterprise_number
    const matchesSearch =
      !searchQuery.value ||
      req.id.toString().includes(searchQuery.value) ||
      (req.enterprise_number && req.enterprise_number.toString().includes(searchQuery.value));

    // Filter by language
    const matchesLang = !selectedLang.value || req.langue_entreprise === selectedLang.value;

    // Filter by service
    let matchesService = true;
    if (selectedService.value === "address") matchesService = req.address_service === 1;
    else if (selectedService.value === "dirigeants") matchesService = req.dirigeants_service === 1;
    else if (selectedService.value === "cession_parts") matchesService = req.cession_parts_service === 1;

    return matchesSearch && matchesLang && matchesService;
  });
});

const resumeDemande = (demande_id) => {
  const demande = requests.value.find(d => d.id === demande_id);
  sessionStorage.setItem("demande_id", demande_id);
  let routeName = "form";
  if (demande && demande.progress !== undefined && demande.progress !== null) {
    const progress = Number(demande.progress);
    if (progress < 25) routeName = "step1Validate";
    else if (progress < 50) routeName = "servicesStep";
    else if (progress < 75) routeName = "step2";
    else if (progress < 100) routeName = "step3";
    else routeName = "finish";
  }
  router.push({ name: routeName, query: { demande_id } });
};
import { useI18n } from "vue-i18n";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import {
  Plus,
  FileText,
  Mail,
  ListChecks,
  Loader,
  Clock,
  MapPin,
  Users,
  Search,
  Globe,
  Filter,
  ChevronDown,
  RotateCcw,
  X,
  FolderClock,
  FolderOpen,
  Hash,
  Download,
  Pencil,
  Trash2,
  ArrowRight,
  AlertTriangle,
} from "lucide-vue-next";

const { t } = useI18n();
const router = useRouter();

const count = ref({ demandes: 0, reclamation: 0 });
const requests = ref([]);
const messages = ref([]);
const showDeleteModal = ref(false);
const showIdModal = ref(false);
const fullIdValue = ref("");
const openMenuId = ref(null);
const deleting = ref(false);
const demandeToDelete = ref(null);

const tableHeaders = [
    "dashboard.id",
    "dashboard.numero_entreprise",
    "dashboard.langue",
    "dashboard.services",
    "dashboard.date_creation",
];

const translatedHeaders = computed(() => tableHeaders.map((key) => t(key)));

// Aurora lucide icons mapped per stat card (parallel to `stats` order).
const statIcons = [FileText, Mail, ListChecks];

const stats = ref([
    {
        title: "dashboard.requests",
        count: count.value.demandes,
        icon: "fas fa-hand-paper",
        bgColor: "#059669",
    },
    {
        title: "dashboard.claims",
        count: count.value.reclamation,
        icon: "fas fa-envelope",
        bgColor: "#059669",
    },
    {
        title: "dashboard.in_progress",
        count: demandesInProgress.value.length,
        icon: "fas fa-tasks",
        bgColor: "#059669",
    },
]);

const refreshData = async (showLoader = false) => {
  if (showLoader) {
    loading.value = true;
  }
  try {
    await Promise.all([
      getDemandes(),
      getTickets(),
      getCount(),
    ]);
  } finally {
    if (showLoader) {
      loading.value = false;
    }
  }
};

const handleFocus = () => refreshData(false);

const handleVisibility = () => {
  if (document.visibilityState === "visible") {
    refreshData(false);
  }
};

onMounted(() => {
  refreshData(true);
  window.addEventListener("focus", handleFocus);
  document.addEventListener("visibilitychange", handleVisibility);
});

onBeforeUnmount(() => {
  window.removeEventListener("focus", handleFocus);
  document.removeEventListener("visibilitychange", handleVisibility);
});

const getDemandes = async () => {
    try {
        const response = await axiosInstance.get("mydemandes");
        requests.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

const getTickets = async () => {
    try {
        const response = await axiosInstance.get("mytickets");
        messages.value = response.data;
    } catch (error) {
        console.error(error);
    }
};

const getCount = async () => {
    try {
        const response = await axiosInstance.get("count-client");
        if (response.data) {
            count.value.demandes = response.data.demandes;
            count.value.reclamation = response.data.tickets;
        }
    } catch (error) {
        console.error(error);
    }
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toISOString().replace("T", " ").split(".")[0];
};

const goToNewDemande = () => {
  router.push({ name: "step1" });
};

const goToDemandeFiles = (demande_id) => {
    sessionStorage.setItem("demande_id", demande_id);
    router.push({name:'form',query: { demande_id }});
};

const toggleMenu = (id) => {
  openMenuId.value = openMenuId.value === id ? null : id;
};

const goToModify = (demande_id) => {
  openMenuId.value = null;
  sessionStorage.setItem("demande_id", demande_id);
  router.push({ name: "demandeModify", query: { demande_id } });
};

const openIdModal = (id) => {
  openMenuId.value = null;
  fullIdValue.value = id;
  showIdModal.value = true;
};

const closeIdModal = () => {
  showIdModal.value = false;
  fullIdValue.value = "";
};

const downloadDossier = (demandeId) => {
  openMenuId.value = null;
  const link = document.createElement("a");
  link.href = `/api/demandes/${demandeId}/download-all`;
  link.target = "_blank";
  link.rel = "noopener";
  link.click();
};

const openDeleteModal = (demande) => {
    demandeToDelete.value = demande;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    demandeToDelete.value = null;
};

const confirmDelete = async () => {
    if (!demandeToDelete.value) return;
    try {
        deleting.value = true;
        await axiosInstance.delete(`demandes/${demandeToDelete.value.id}`);
        requests.value = requests.value.filter(r => r.id !== demandeToDelete.value.id);
        if (count.value && typeof count.value.demandes === "number" && count.value.demandes > 0) {
            count.value.demandes = count.value.demandes - 1;
        }
    } catch (e) {
        console.error(e);
    } finally {
        deleting.value = false;
        cancelDelete();
    }
};

watch(
    count,
    (newCount) => {
        stats.value = [
            {
                title: "dashboard.requests",
                count: newCount.demandes,
                icon: "fas fa-hand-paper",
                bgColor: "#059669",
            },
            {
                title: "dashboard.claims",
                count: newCount.reclamation,
                icon: "fas fa-envelope",
                bgColor: "#059669",
            },
            {
                title: "dashboard.in_progress",
                count: demandesInProgress.value.length,
                icon: "fas fa-tasks",
                bgColor: "#059669",
            },
        ];
    },
    { deep: true }
);
const clearAllFilters = () => {
  searchQuery.value = '';
  selectedLang.value = '';
  selectedService.value = '';
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
