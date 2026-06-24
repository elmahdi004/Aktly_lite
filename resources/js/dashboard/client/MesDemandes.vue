<!-- MesDemandes.vue — Aurora Glass rebuild -->
<template>
    <div class="aurora-wrap relative min-h-screen w-full">
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:420px;height:420px;background:#a7f3d0;top:-180px;right:-100px;opacity:.20;"></div>
            <div class="aurora-blob" style="width:360px;height:360px;background:#6ee7b7;bottom:-200px;left:-120px;opacity:.14;"></div>
        </div>

        <div class="relative z-10 mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- heading -->
            <div class="animate-fade-up mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        {{ $t('dashboard.mes_demandes') }}
                    </h1>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $t('dashboard.requests') }}</p>
                </div>
                <span
                    v-if="requests && requests.length"
                    class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/15"
                >
                    <Layers class="h-4 w-4" />
                    {{ filteredRequests.length }} / {{ requests.length }}
                </span>
            </div>

            <!-- filters -->
            <div class="animate-fade-up-1 glass-card mb-6 p-4 sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <!-- search -->
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="input-aurora pl-11 pr-10"
                            :placeholder="$t('dashboard.search')"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- language segmented pills -->
                    <div class="flex flex-col gap-1.5">
                        <span class="px-1 text-xs font-medium text-slate-500">{{ $t('dashboard.language') }}</span>
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white/70 p-1 backdrop-blur">
                            <button
                                type="button"
                                @click="selectedLang = ''"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedLang === '' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                {{ $t('dashboard.all_languages') }}
                            </button>
                            <button
                                type="button"
                                @click="selectedLang = 'fr'"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedLang === 'fr' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                🇫🇷 FR
                            </button>
                            <button
                                type="button"
                                @click="selectedLang = 'nl'"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedLang === 'nl' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                🇳🇱 NL
                            </button>
                        </div>
                    </div>

                    <!-- service segmented pills -->
                    <div class="flex flex-col gap-1.5">
                        <span class="px-1 text-xs font-medium text-slate-500">{{ $t('dashboard.service') }}</span>
                        <div class="inline-flex flex-wrap rounded-xl border border-slate-200 bg-white/70 p-1 backdrop-blur">
                            <button
                                type="button"
                                @click="selectedService = ''"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedService === '' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                {{ $t('dashboard.all_services') }}
                            </button>
                            <button
                                type="button"
                                @click="selectedService = 'address'"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedService === 'address' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <MapPin class="h-3.5 w-3.5" /> {{ $t('dashboard.adresse') }}
                            </button>
                            <button
                                type="button"
                                @click="selectedService = 'dirigeants'"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedService === 'dirigeants' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <Users class="h-3.5 w-3.5" /> {{ $t('dashboard.dirigeants') }}
                            </button>
                            <button
                                type="button"
                                @click="selectedService = 'cession_parts'"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="selectedService === 'cession_parts' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >
                                <FileText class="h-3.5 w-3.5" /> {{ $t('dashboard.cession_parts') }}
                            </button>
                        </div>
                    </div>

                    <!-- clear -->
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="clearAllFilters"
                        class="inline-flex items-center justify-center gap-1.5 self-end rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                    >
                        <RotateCcw class="h-4 w-4" /> {{ $t('dashboard.clear_filters') }}
                    </button>
                </div>
            </div>

            <!-- ===== desktop table ===== -->
            <div v-if="filteredRequests.length > 0" class="animate-fade-up-2 hidden overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/60 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('dashboard.numero_entreprise') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('dashboard.langue') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('dashboard.service') }}</th>
                                <th class="px-5 py-3.5 text-right font-semibold">{{ $t('dashboard.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="request in filteredRequests"
                                :key="request.id"
                                class="transition-colors hover:bg-emerald-50/40"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700">
                                            <Building2 class="h-[18px] w-[18px]" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-mono text-sm font-semibold text-slate-800">{{ request.enterprise_number }}</p>
                                            <button
                                                type="button"
                                                @click="openIdModal(request.id)"
                                                class="truncate text-xs text-slate-400 transition hover:text-emerald-600"
                                            >
                                                #{{ request.id }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">
                                        {{ request.langue_entreprise === 'nl' ? '🇳🇱' : '🇫🇷' }}
                                        {{ (request.langue_entreprise || '').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-if="request.address_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                                            <MapPin class="h-3 w-3" /> {{ $t('dashboard.adresse') }}
                                        </span>
                                        <span v-if="request.dirigeants_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                                            <Users class="h-3 w-3" /> {{ $t('dashboard.dirigeants') }}
                                        </span>
                                        <span v-if="request.cession_parts_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                                            <FileText class="h-3 w-3" /> {{ $t('dashboard.cession_parts') }}
                                        </span>
                                        <span v-if="request.address_service !== 1 && request.dirigeants_service !== 1 && request.cession_parts_service !== 1" class="text-xs text-slate-300">—</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="goToDemandeFiles(request.id)"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                        >
                                            <Eye class="h-3.5 w-3.5" /> {{ $t('dashboard.view_or_download') }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="goToModify(request.id)"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                                        >
                                            <Pencil class="h-3.5 w-3.5" /> {{ $t('dashboard.modify') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===== mobile glass cards ===== -->
            <div v-if="filteredRequests.length > 0" class="animate-fade-up-2 grid gap-4 md:hidden">
                <div
                    v-for="request in filteredRequests"
                    :key="request.id"
                    class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-start gap-3">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-bold text-slate-900">{{ request.enterprise_number }}</p>
                            <button
                                type="button"
                                @click="openIdModal(request.id)"
                                class="truncate text-xs text-slate-400 transition hover:text-emerald-600"
                            >
                                #{{ request.id }}
                            </button>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">
                            {{ request.langue_entreprise === 'nl' ? '🇳🇱' : '🇫🇷' }}
                            {{ (request.langue_entreprise || '').toUpperCase() }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span v-if="request.address_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                            <MapPin class="h-3 w-3" /> {{ $t('dashboard.adresse') }}
                        </span>
                        <span v-if="request.dirigeants_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                            <Users class="h-3 w-3" /> {{ $t('dashboard.dirigeants') }}
                        </span>
                        <span v-if="request.cession_parts_service === 1" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/15">
                            <FileText class="h-3 w-3" /> {{ $t('dashboard.cession_parts') }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            @click="goToDemandeFiles(request.id)"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        >
                            <Eye class="h-3.5 w-3.5" /> {{ $t('dashboard.view_or_download') }}
                        </button>
                        <button
                            type="button"
                            @click="goToModify(request.id)"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                        >
                            <Pencil class="h-3.5 w-3.5" /> {{ $t('dashboard.modify') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== empty state ===== -->
            <div v-if="filteredRequests.length === 0" class="animate-fade-up-2 glass-card flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="grid h-16 w-16 place-items-center rounded-3xl bg-emerald-50 text-emerald-600">
                    <FolderOpen class="h-8 w-8" />
                </div>
                <h3 class="font-display mt-5 text-lg font-bold text-slate-900">{{ $t('dashboard.mes_demandes') }}</h3>
                <p class="mt-1.5 max-w-sm text-sm text-slate-500">
                    {{ hasActiveFilters ? $t('dashboard.active_filters') : $t('dashboard.requests') }}
                </p>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="clearAllFilters"
                    class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                >
                    <RotateCcw class="h-4 w-4" /> {{ $t('dashboard.clear_filters') }}
                </button>
            </div>
        </div>

        <!-- ===== full ID modal ===== -->
        <transition name="fade">
            <div v-if="showIdModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeIdModal"></div>
                <div class="glass-card animate-fade-up relative w-full max-w-md p-7">
                    <div class="mb-3 flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <Hash class="h-5 w-5" />
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900">{{ $t('dashboard.id_full_title') }}</h3>
                    </div>
                    <p class="break-all rounded-xl bg-slate-50 px-4 py-3 font-mono text-sm text-slate-700">{{ fullIdValue }}</p>
                    <div class="mt-6 flex justify-end">
                        <button @click="closeIdModal" type="button" class="btn-aurora">
                            {{ $t('dashboard.ok') }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { Search, X, RotateCcw, Layers, Building2, MapPin, Users, FileText, Eye, Pencil, Hash, FolderOpen } from "lucide-vue-next";

const requests = ref([]);
const showIdModal = ref(false);
const fullIdValue = ref("");
const openMenuId = ref(null);
const router = useRouter();

const searchQuery = ref("");
const selectedLang = ref("");
const selectedService = ref("");

const hasActiveFilters = computed(() => !!(searchQuery.value || selectedLang.value || selectedService.value));

const filteredRequests = computed(() => {
    return requests.value.filter((req) => {
        const matchesSearch =
            !searchQuery.value ||
            req.id.toString().includes(searchQuery.value) ||
            (req.enterprise_number && req.enterprise_number.toString().includes(searchQuery.value));

        const matchesLang = !selectedLang.value || req.langue_entreprise === selectedLang.value;

        let matchesService = true;
        if (selectedService.value === "address") matchesService = req.address_service === 1;
        else if (selectedService.value === "dirigeants") matchesService = req.dirigeants_service === 1;
        else if (selectedService.value === "cession_parts") matchesService = req.cession_parts_service === 1;

        return matchesSearch && matchesLang && matchesService;
    });
});

const clearAllFilters = () => {
    searchQuery.value = "";
    selectedLang.value = "";
    selectedService.value = "";
};

onMounted(() => {
    getDemandes();
});
const getDemandes = async () => {
    try {
        const response = await axiosInstance.get("mydemandes");
        requests.value = response.data;
        // console.log(response.data);
    } catch (error) {
        console.error(error);
    }
};
const goToDemandeFiles = (demande_id) =>{
router.push({name:'form',query: { demande_id }});
}

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

const toggleMenu = (id) => {
    openMenuId.value = openMenuId.value === id ? null : id;
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
