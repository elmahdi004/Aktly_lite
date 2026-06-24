<template>
    <div class="animate-fade-up">
        <div class="mb-7 text-center">
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                {{ isNl ? "Nieuw adres & data" : "Nouvelle adresse & dates" }}
            </h1>
            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                {{ isNl ? "Geef het nieuwe adres van de maatschappelijke zetel en de wijzigingsdatum op." : "Indiquez la nouvelle adresse du siège social et la date du changement." }}
            </p>
        </div>

        <!-- current address -->
        <div v-if="currentAddress" class="mb-4 flex items-start gap-3 rounded-2xl border border-slate-100 bg-white/70 p-4">
            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ isNl ? "Huidig adres" : "Adresse actuelle" }}</p>
                <p class="text-sm text-slate-700">{{ currentAddress }}</p>
            </div>
        </div>

        <!-- new address -->
        <div class="glass-card p-6 sm:p-8">
            <h3 class="mb-4 flex items-center gap-2 font-semibold text-slate-800"><Navigation class="h-4 w-4 text-emerald-600" /> {{ isNl ? "Nieuw adres" : "Nouvelle adresse" }}</h3>

            <!-- search -->
            <div class="relative">
                <Search class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <input v-model="searchQuery" @input="onSearch" :placeholder="isNl ? 'Zoek een adres…' : 'Rechercher une adresse…'" class="input-aurora pl-11" />
                <div v-if="searching" class="absolute right-3 top-1/2 -translate-y-1/2"><span class="block h-4 w-4 animate-spin rounded-full border-2 border-emerald-500/30 border-t-emerald-500"></span></div>
                <ul v-if="suggestions.length" class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                    <li v-for="s in suggestions" :key="s.place_id" @click="pickSuggestion(s)" class="cursor-pointer px-4 py-2.5 text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">{{ s.display_name }}</li>
                </ul>
            </div>

            <!-- manual fields -->
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.rue") || "Rue" }}</label>
                    <input v-model="rue" type="text" class="input-aurora" />
                </div>
                <div class="sm:col-span-1">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.numero") || "N°" }}</label>
                    <input v-model="numero" type="text" class="input-aurora" />
                </div>
                <div class="sm:col-span-1">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.box") || "Boîte" }}</label>
                    <input v-model="box" type="text" class="input-aurora" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.zip_code") || "Code postal" }}</label>
                    <input v-model="zip_code" type="text" class="input-aurora" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.localite") || "Localité" }}</label>
                    <input v-model="localite" type="text" class="input-aurora" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("address.pays") || "Pays" }}</label>
                    <input :value="isNl ? 'België' : 'Belgique'" disabled type="text" class="input-aurora bg-slate-50 text-slate-400" />
                </div>
            </div>
        </div>

        <!-- dates -->
        <div class="glass-card mt-4 p-6 sm:p-8">
            <h3 class="mb-4 flex items-center gap-2 font-semibold text-slate-800"><CalendarDays class="h-4 w-4 text-emerald-600" /> {{ isNl ? "Datums" : "Dates" }}</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("step3.changeDate") || "Date du changement" }}</label>
                    <input v-model="date_changement" type="date" class="input-aurora" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("step3.faitA") || "Fait à" }}</label>
                    <input v-model="fait_a" type="text" :placeholder="isNl ? 'bv. Brussel' : 'ex. Bruxelles'" class="input-aurora" />
                </div>
            </div>

            <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-white/70 px-4 py-3 transition hover:border-emerald-300">
                <span class="grid h-5 w-5 place-items-center rounded-md border-2 transition" :class="sameAsChange ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'">
                    <Check v-if="sameAsChange" class="h-3.5 w-3.5 text-white" />
                </span>
                <input type="checkbox" v-model="sameAsChange" class="sr-only" />
                <span class="text-sm text-slate-700">{{ isNl ? "Datum algemene vergadering identiek aan de wijzigingsdatum" : "Date de l'assemblée générale identique à la date de changement" }}</span>
            </label>

            <div v-if="!sameAsChange" class="mt-4">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("step3.generalMeetingDate") || "Date de l'assemblée générale" }}</label>
                <input v-model="date_assemblee" type="datetime-local" class="input-aurora sm:max-w-xs" />
            </div>
        </div>

        <div v-if="error" class="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
            <AlertCircle class="h-4 w-4 shrink-0" /> {{ error }}
        </div>

        <!-- nav -->
        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" @click="prev" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800">
                <ArrowLeft class="h-4 w-4" /> {{ $t("step1Validate.previousButton") }}
            </button>
            <button type="button" :disabled="loading" @click="submit" class="btn-aurora">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? ($t("services.loading") || "…") : ($t("services.nextButton") || "Continuer") }}</span>
                <ArrowRight v-if="!loading" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { MapPin, Navigation, Search, CalendarDays, Check, AlertCircle, ArrowLeft, ArrowRight } from "lucide-vue-next";

const { t, locale } = useI18n();
const router = useRouter();
const updateStep = inject("updateStep", null);
const isNl = computed(() => locale.value === "nl");

const demandeId = sessionStorage.getItem("demande_id");
const currentAddress = ref("");
const rue = ref("");
const numero = ref("");
const box = ref("");
const zip_code = ref("");
const localite = ref("");
const pays = ref("Belgique");
const date_changement = ref("");
const date_assemblee = ref("");
const sameAsChange = ref(true);
const fait_a = ref("");
const searchQuery = ref("");
const suggestions = ref([]);
const searching = ref(false);
const selectedSuggestion = ref(null);
const loading = ref(false);
const error = ref("");
let searchTimer = null;

onMounted(async () => {
    if (updateStep) updateStep(3);
    await loadDemande();
    await loadExistingAddress();
});

const fmtBceAddress = (a) => {
    if (!a) return "";
    const get = (v) => (v && typeof v === "object" ? v.description?.value ?? v.value ?? "" : v ?? "");
    return [get(a.street), a.houseNumber ?? "", a.box ? "bte " + a.box : "", a.zipcode ?? a.zipCode ?? "", get(a.municipality), a.country ?? ""]
        .filter((p) => p !== "" && p != null).join(" ").replace(/\s+/g, " ").trim();
};

const loadDemande = async () => {
    try {
        const { data } = await axiosInstance.get(`demandes/${demandeId}`);
        const d = data.demande;
        const bce = typeof d.bce_data === "string" ? JSON.parse(d.bce_data) : d.bce_data;
        currentAddress.value = fmtBceAddress(bce?.address);
        if (d.date_changement) date_changement.value = String(d.date_changement).slice(0, 10);
        if (d.fait_a) fait_a.value = d.fait_a;
        if (d.date_assemblee) {
            date_assemblee.value = String(d.date_assemblee).replace(" ", "T").slice(0, 16);
            sameAsChange.value = false;
        }
    } catch (e) {
        /* ignore */
    }
};

const loadExistingAddress = async () => {
    try {
        const { data } = await axiosInstance.get(`/addressService/getByIdDemande/${demandeId}`);
        const a = data.address;
        if (a) {
            rue.value = a.rue || "";
            numero.value = a.numero || "";
            box.value = a.box || "";
            zip_code.value = a.zip_code || "";
            localite.value = a.localite || "";
            pays.value = a.pays || "Belgique";
        }
    } catch (e) {
        /* ignore */
    }
};

const onSearch = () => {
    if (searchTimer) clearTimeout(searchTimer);
    selectedSuggestion.value = null;
    searchTimer = setTimeout(async () => {
        const q = searchQuery.value.trim();
        if (q.length < 3) { suggestions.value = []; return; }
        searching.value = true;
        try {
            const { data } = await axiosInstance.get("/nominatim/search", { params: { q } });
            suggestions.value = data || [];
        } catch (e) {
            suggestions.value = [];
        } finally {
            searching.value = false;
        }
    }, 350);
};

const pickSuggestion = (s) => {
    selectedSuggestion.value = s;
    const a = s.address || {};
    rue.value = a.road || "";
    numero.value = a.house_number || "";
    box.value = "";
    zip_code.value = a.postcode || "";
    localite.value = a.city || a.town || a.village || a.municipality || "";
    pays.value = a.country || "Belgique";
    searchQuery.value = s.display_name;
    suggestions.value = [];
};

const prev = () => router.push({ name: "step2" });

const submit = async () => {
    error.value = "";
    if (!rue.value || !zip_code.value || !localite.value) {
        error.value = isNl.value ? "Vul het nieuwe adres in." : "Veuillez compléter la nouvelle adresse.";
        return;
    }
    if (!date_changement.value || !fait_a.value) {
        error.value = isNl.value ? "Vul de datum en plaats in." : "Veuillez indiquer la date de changement et le lieu.";
        return;
    }
    loading.value = true;
    try {
        await axiosInstance.post("addressService", {
            an_address: currentAddress.value || "-",
            rue: rue.value,
            numero: numero.value || "-",
            box: box.value,
            zip_code: zip_code.value,
            localite: localite.value,
            pays: pays.value || "Belgique",
            demande_id: demandeId,
        });

        const assemblee = sameAsChange.value
            ? `${date_changement.value} 00:00`
            : date_assemblee.value
            ? date_assemblee.value.replace("T", " ")
            : `${date_changement.value} 00:00`;

        await axiosInstance.put(`/demandes/${demandeId}`, {
            date_changement: date_changement.value,
            date_assemblee: assemblee,
            fait_a: fait_a.value,
        });

        try {
            await axiosInstance.post(`/demandes/${demandeId}/progress`, { progress: 100 });
        } catch (_) {}

        router.push({ name: "form", query: { demande_id: demandeId } });
    } catch (e) {
        error.value = t("step2.errorServer") || "Une erreur est survenue.";
    } finally {
        loading.value = false;
    }
};
</script>
