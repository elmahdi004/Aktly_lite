<template>
    <div class="animate-fade-up">
        <!-- heading -->
        <div class="mb-7 text-center">
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                {{ $t("step1.title") }}
            </h1>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ $t("step1.description") }}</p>
        </div>

        <div class="glass-card p-6 sm:p-8">
            <!-- ===== search form (hidden once a result is shown) ===== -->
            <form v-if="!result" method="POST" @submit.prevent="search()" class="space-y-6">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("step1.placeholder") }}</label>
                    <div class="relative">
                        <Building2 class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="enterprise_number"
                            @input="error = ''"
                            inputmode="numeric"
                            maxlength="10"
                            :placeholder="$t('step1.placeholder')"
                            class="input-aurora pl-11 font-mono tracking-wide"
                            :class="error ? '!border-red-400 focus:!ring-red-400/15' : ''"
                        />
                    </div>
                    <p class="mt-1.5 text-xs text-slate-400">{{ isNl ? "10 cijfers, bv. 0860156426" : "10 chiffres, ex. 0860156426" }}</p>
                </div>

                <!-- language selector -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">{{ $t("step1.selectLanguage") }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="langue = 'fr'"
                            class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                            :class="langue === 'fr' ? 'border-emerald-500 bg-emerald-50/60 ring-4 ring-emerald-500/10' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <span class="text-2xl leading-none">🇫🇷</span>
                            <span class="flex-1">
                                <span class="block text-sm font-semibold text-slate-800">{{ $t("step1.french") }}</span>
                                <span class="block text-xs text-slate-400">Français</span>
                            </span>
                            <span class="grid h-5 w-5 place-items-center rounded-full border-2 transition" :class="langue === 'fr' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'">
                                <Check v-if="langue === 'fr'" class="h-3 w-3 text-white" />
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="langue = 'nl'"
                            class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                            :class="langue === 'nl' ? 'border-emerald-500 bg-emerald-50/60 ring-4 ring-emerald-500/10' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <span class="text-2xl leading-none">🇳🇱</span>
                            <span class="flex-1">
                                <span class="block text-sm font-semibold text-slate-800">{{ $t("step1.dutch") }}</span>
                                <span class="block text-xs text-slate-400">Nederlands</span>
                            </span>
                            <span class="grid h-5 w-5 place-items-center rounded-full border-2 transition" :class="langue === 'nl' ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'">
                                <Check v-if="langue === 'nl'" class="h-3 w-3 text-white" />
                            </span>
                        </button>
                    </div>
                </div>

                <div v-if="error" class="flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                    <AlertCircle class="h-4 w-4 shrink-0" /> {{ error }}
                </div>

                <button :disabled="loading" type="submit" class="btn-aurora w-full">
                    <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <Search v-else class="h-4 w-4" />
                    <span>{{ loading ? $t("step1.loading") : $t("step1.searchButton") }}</span>
                </button>
            </form>

            <!-- ===== inline result (same page) ===== -->
            <div v-else class="animate-fade-up">
                <div class="mb-5 flex items-center gap-3">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">{{ isNl ? "Onderneming gevonden" : "Entreprise trouvée" }}</p>
                        <p class="font-display text-lg font-bold leading-tight text-slate-900">{{ result.nom || "—" }}</p>
                    </div>
                </div>

                <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100">
                    <div class="flex items-start gap-3 px-4 py-3.5">
                        <Hash class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-400">{{ $t("step1Validate.companyNumber") }}</dt>
                            <dd class="font-mono text-sm text-slate-800">{{ result.numero }}</dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3.5">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-400">{{ $t("step1Validate.address") }}</dt>
                            <dd class="text-sm text-slate-800">{{ result.adresse || "—" }}</dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3.5">
                        <Globe class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-400">{{ $t("step1.selectLanguage") }}</dt>
                            <dd class="text-sm text-slate-800">{{ langue === "nl" ? "🇳🇱 Nederlands" : "🇫🇷 Français" }}</dd>
                        </div>
                    </div>
                </dl>

                <button type="button" @click="resetSearch" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-emerald-700">
                    <Pencil class="h-3.5 w-3.5" /> {{ isNl ? "Wijzig de zoekopdracht" : "Modifier la recherche" }}
                </button>

                <div v-if="error" class="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                    <AlertCircle class="h-4 w-4 shrink-0" /> {{ error }}
                </div>
            </div>
        </div>

        <!-- nav buttons -->
        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" disabled class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-400 opacity-50">
                <ArrowLeft class="h-4 w-4" /> {{ $t("step1Validate.previousButton") }}
            </button>
            <button v-if="result" :disabled="loading" type="button" @click="next" class="btn-aurora">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? $t("step1Validate.loading") : $t("step1Validate.nextButton") }}</span>
                <ArrowRight v-if="!loading" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed, inject } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { Building2, Search, Check, Hash, MapPin, Globe, Pencil, ArrowRight, ArrowLeft, AlertCircle } from "lucide-vue-next";

const { t, locale } = useI18n();
const router = useRouter();
const updateStep = inject("updateStep", null);

const isNl = computed(() => locale.value === "nl");

const enterprise_number = ref("");
// FR-first default for the Belgian market; the user can switch with the flag selector.
const langue = ref("fr");
const loading = ref(false);
const error = ref("");
const result = ref(null);

onMounted(async () => {
    sessionStorage.removeItem("demande_id");
    localStorage.removeItem("demande_id");
    await checkAccess();
    if (updateStep) updateStep(1);
});

const checkAccess = async () => {
    try {
        const { data } = await axiosInstance.get("/billing/subscription");
        const ok = data.active || Number(data.credits || 0) > 0 ||
            (data.applied_coupon_code && data.coupon_expires_at && new Date(data.coupon_expires_at) > new Date() && data.coupon_dossiers_remaining > 0);
        if (!ok) {
            router.push({ name: "buyCredits" });
            return false;
        }
        return true;
    } catch (e) {
        router.push({ name: "buyCredits" });
        return false;
    }
};

const buildAddress = (bce) => {
    const a = bce?.address || {};
    const get = (v) => (v && typeof v === "object" ? v.description?.value ?? v.value ?? "" : v ?? "");
    const parts = [
        get(a.street),
        a.houseNumber ?? "",
        a.box ? "bte " + a.box : "",
        a.zipcode ?? a.zipCode ?? "",
        get(a.municipality),
        a.country ?? "",
    ].filter((p) => p !== "" && p != null);
    return parts.join(" ").replace(/\s+/g, " ").trim();
};

const search = async () => {
    error.value = "";
    if (!enterprise_number.value) {
        error.value = t("step1.errorNoEnterpriseNumber");
        return;
    }
    if (enterprise_number.value.length !== 10) {
        error.value = t("step1.errorInvalidEnterpriseNumber");
        return;
    }
    loading.value = true;
    try {
        const { data } = await axiosInstance.post("/getEnterpriseByNumber", {
            enterprise_number: enterprise_number.value,
            langue: langue.value,
        });
        if (!data || !data.typeOfEnterprise) {
            error.value = t("step1.errorNoEnterpriseFound");
            return;
        }
        if (data.typeOfEnterprise !== "ELP") {
            error.value = t("step1.errorIndividualEnterprise");
            return;
        }
        const status = data?.juridicalSituation?.status?.description?.value;
        if (status !== "Actif" && status !== "Actief") {
            error.value = t("step1.errorInactiveEnterprise");
            return;
        }
        const nom = data?.denomination?.description?.value ?? data?.denomination?.[0]?.description?.value ?? "";
        result.value = {
            nom,
            numero: enterprise_number.value,
            adresse: buildAddress(data),
            bce_data: data,
        };
    } catch (e) {
        error.value = t("step1.errorSearch");
    } finally {
        loading.value = false;
    }
};

const resetSearch = () => {
    result.value = null;
    error.value = "";
};

const next = async () => {
    if (!result.value || loading.value) return;
    loading.value = true;
    error.value = "";
    try {
        const { data } = await axiosInstance.post("/demandes", {
            enterprise_number: result.value.numero,
            bce_data: result.value.bce_data,
            langue: langue.value,
        });
        const demandeId = data.demande_id;
        sessionStorage.setItem("demande_id", demandeId);
        // import BCE dirigeants so the dépositaire step can list them
        try {
            await axiosInstance.post("dirigeantsService", { demande_id: demandeId });
        } catch (_) {}
        try {
            await axiosInstance.post(`/demandes/${demandeId}/progress`, { progress: 25 });
        } catch (_) {}
        router.push({ name: "step2" });
    } catch (e) {
        error.value = e?.response?.data?.message || t("step1.errorSearch");
    } finally {
        loading.value = false;
    }
};
</script>
