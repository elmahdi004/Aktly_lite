<template>
    <div class="animate-fade-up">
        <div class="mb-7 text-center">
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                {{ isNl ? "Indiener van het dossier" : "Dépositaire du dossier" }}
            </h1>
            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                {{ isNl ? "Selecteer de bestuurder die het dossier indient en verifieer zijn identiteit." : "Sélectionnez le dirigeant qui déposera le dossier, puis vérifiez son identité." }}
            </p>
        </div>

        <div class="glass-card p-6 sm:p-8">
            <!-- loading -->
            <div v-if="loadingList" class="flex flex-col items-center justify-center gap-3 py-10 text-slate-400">
                <span class="h-6 w-6 animate-spin rounded-full border-2 border-emerald-500/30 border-t-emerald-500"></span>
                <span class="text-sm">{{ isNl ? "Bestuurders laden…" : "Chargement des dirigeants…" }}</span>
            </div>

            <!-- empty -->
            <div v-else-if="!dirigeants.length" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400"><Users class="h-7 w-7" /></div>
                <p class="text-sm text-slate-500">{{ isNl ? "Geen bestuurder gevonden voor deze onderneming." : "Aucun dirigeant trouvé pour cette entreprise." }}</p>
            </div>

            <!-- dirigeant cards -->
            <div v-else>
                <p class="mb-3 text-sm font-medium text-slate-700">{{ isNl ? "Kies één bestuurder als beheerder" : "Choisissez un dirigeant comme administrateur" }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        v-for="d in dirigeants"
                        :key="d.id"
                        type="button"
                        @click="select(d)"
                        class="flex items-start gap-3 rounded-2xl border-2 p-4 text-left transition-all"
                        :class="selectedId === d.id ? 'border-emerald-500 bg-emerald-50/60 ring-4 ring-emerald-500/10' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full font-semibold" :class="selectedId === d.id ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'">
                            {{ initials(d) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-800">{{ (d.givenName || "") + " " + (d.surname || "") }}</p>
                            <p v-if="d.function" class="truncate text-xs text-slate-500">{{ d.function }}</p>
                            <p v-if="d.rc" class="mt-0.5 truncate font-mono text-xs text-slate-400">{{ d.rc }}</p>
                        </div>
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 transition" :class="selectedId === d.id ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'">
                            <Check v-if="selectedId === d.id" class="h-3 w-3 text-white" />
                        </span>
                    </button>
                </div>

                <!-- verification panel -->
                <transition name="reveal">
                    <div v-if="selected" class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5">
                        <div class="flex items-center gap-2.5">
                            <ShieldCheck class="h-5 w-5 text-emerald-600" />
                            <h3 class="font-semibold text-slate-800">{{ isNl ? "Identiteitsverificatie (Veriff)" : "Vérification d'identité (Veriff)" }}</h3>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isNl ? "Stuur een sms met een beveiligde Veriff-link naar de bestuurder om zijn identiteitskaart te scannen." : "Envoyez par SMS un lien Veriff sécurisé au dirigeant pour scanner sa carte d'identité." }}
                        </p>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("step2.gsm") || "GSM" }}</label>
                                <div class="relative">
                                    <Phone class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                                    <input v-model="gsm" type="tel" placeholder="+32 4XX XX XX XX" class="input-aurora pl-11" :class="verifyError ? '!border-red-400' : ''" />
                                </div>
                            </div>
                            <button type="button" @click="sendLink" :disabled="sending" class="btn-aurora sm:w-auto">
                                <span v-if="sending" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                <Send v-else class="h-4 w-4" />
                                <span>{{ isNl ? "Link versturen" : "Envoyer le lien" }}</span>
                            </button>
                        </div>

                        <div v-if="verifyError" class="mt-3 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600">
                            <AlertCircle class="h-4 w-4 shrink-0" /> {{ verifyError }}
                        </div>
                        <div v-if="linkSent" class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-100/70 px-4 py-2.5 text-sm font-medium text-emerald-700">
                            <CheckCircle2 class="h-4 w-4 shrink-0" /> {{ isNl ? "Sms verstuurd — de bestuurder kan nu zijn identiteit verifiëren." : "SMS envoyé — le dirigeant peut maintenant vérifier son identité." }}
                        </div>
                    </div>
                </transition>
            </div>

            <div v-if="error" class="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                <AlertCircle class="h-4 w-4 shrink-0" /> {{ error }}
            </div>
        </div>

        <!-- nav -->
        <div class="mt-6 flex items-center justify-between gap-3">
            <button type="button" @click="prev" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800">
                <ArrowLeft class="h-4 w-4" /> {{ $t("step1Validate.previousButton") }}
            </button>
            <button type="button" :disabled="!selectedId || loading" @click="next" class="btn-aurora">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? ($t("step2.loading") || "…") : ($t("step2.continue") || "Continuer") }}</span>
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
import { Users, Check, ShieldCheck, Phone, Send, CheckCircle2, AlertCircle, ArrowLeft, ArrowRight } from "lucide-vue-next";

const { t, locale } = useI18n();
const router = useRouter();
const updateStep = inject("updateStep", null);
const isNl = computed(() => locale.value === "nl");

const demandeId = sessionStorage.getItem("demande_id");
const dirigeants = ref([]);
const selectedId = ref(null);
const gsm = ref("");
const loadingList = ref(true);
const loading = ref(false);
const sending = ref(false);
const linkSent = ref(false);
const error = ref("");
const verifyError = ref("");

const selected = computed(() => dirigeants.value.find((d) => d.id === selectedId.value) || null);

const initials = (d) => ((d.givenName || "").charAt(0) + (d.surname || "").charAt(0)).toUpperCase() || "?";
const isPhoneValid = (p) => /^\+[1-9]\d{7,14}$/.test((p || "").replace(/\s+/g, ""));

onMounted(async () => {
    if (updateStep) updateStep(2);
    await loadDirigeants();
});

const loadDirigeants = async () => {
    loadingList.value = true;
    try {
        const { data } = await axiosInstance.get(`demandes/${demandeId}`);
        const list = data?.demande?.dirigeants || [];
        dirigeants.value = list.filter((d) => Number(d.is_deleted || 0) !== 1);
    } catch (e) {
        error.value = t("step2.errorServer") || "Erreur lors du chargement.";
    } finally {
        loadingList.value = false;
    }
};

const select = (d) => {
    selectedId.value = d.id;
    linkSent.value = false;
    verifyError.value = "";
    gsm.value = d.gsm || d.verification?.GSM || "";
};

const sendLink = async () => {
    verifyError.value = "";
    linkSent.value = false;
    const phone = (gsm.value || "").replace(/\s+/g, "");
    if (!isPhoneValid(phone)) {
        verifyError.value = t("step2.errorInvalidPhoneFormat") || "Format de téléphone invalide (ex. +324XXXXXXXX).";
        return;
    }
    sending.value = true;
    try {
        await axiosInstance.post("/verifications", {
            GSM: phone,
            demande_id: demandeId,
            dirigeant_id: selectedId.value,
        });
        linkSent.value = true;
    } catch (e) {
        verifyError.value = t("step2.errorServer") || "Échec de l'envoi. Réessayez.";
    } finally {
        sending.value = false;
    }
};

const prev = () => router.push({ name: "step1" });

const next = async () => {
    if (!selectedId.value) return;
    loading.value = true;
    error.value = "";
    try {
        // Lite is address-only: ensure the service flag + record the chosen administrator.
        await axiosInstance.put(`/demandes/${demandeId}`, { address_service: 1, depositaire: "gestionnaire" });
        sessionStorage.setItem("depositaire_dirigeant_id", String(selectedId.value));
        try {
            await axiosInstance.post(`/demandes/${demandeId}/progress`, { progress: 50 });
        } catch (_) {}
        router.push({ name: "addressService" });
    } catch (e) {
        error.value = t("step2.errorServer") || "Une erreur est survenue.";
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.reveal-enter-active,
.reveal-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.reveal-enter-from,
.reveal-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
