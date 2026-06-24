<template>
    <div class="mx-auto w-full max-w-2xl px-1 py-4">
        <!-- already has dossiers available -->
        <div v-if="credits > 0" class="glass-card animate-fade-up mb-6 flex flex-col items-center gap-3 p-8 text-center">
            <div class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-600"><CheckCircle2 class="h-7 w-7" /></div>
            <h2 class="font-display text-xl font-extrabold text-slate-900">
                {{ isNl ? `U heeft ${credits} dossier(s) beschikbaar` : `Vous avez ${credits} dossier(s) disponible(s)` }}
            </h2>
            <p class="text-sm text-slate-500">{{ isNl ? 'U kunt meteen een nieuwe aanvraag starten.' : 'Vous pouvez démarrer une nouvelle demande immédiatement.' }}</p>
            <button @click="startDemande" class="btn-aurora mt-1">
                {{ isNl ? 'Nieuwe aanvraag starten' : 'Commencer une demande' }}
                <ArrowRight class="h-4 w-4" />
            </button>
        </div>

        <!-- payment card -->
        <div class="glass-card animate-fade-up p-8 sm:p-10">
            <div class="text-center">
                <div class="brand-gradient mx-auto grid h-12 w-12 place-items-center rounded-2xl">
                    <FileCheck2 class="h-6 w-6 text-white" />
                </div>
                <h1 class="mt-4 font-display text-2xl font-extrabold tracking-tight text-slate-900">
                    {{ isNl ? 'Zetelverplaatsing' : 'Transfert de siège social' }}
                </h1>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                    {{ isNl ? 'Betaal één keer per dossier — geen abonnement, geen verplichting.' : 'Payez une seule fois par dossier — sans abonnement ni engagement.' }}
                </p>
            </div>

            <!-- price -->
            <div class="my-7 flex items-end justify-center gap-1.5">
                <span class="font-display text-5xl font-extrabold tracking-tight text-slate-900">{{ priceLabel }}</span>
                <span class="mb-1.5 text-sm font-medium text-slate-400">/ {{ isNl ? 'dossier' : 'dossier' }}</span>
            </div>

            <!-- included -->
            <ul class="mx-auto max-w-md space-y-3">
                <li v-for="(f, i) in features" :key="i" class="flex items-start gap-3">
                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600"><Check class="h-3 w-3" /></span>
                    <span class="text-sm text-slate-600">{{ f }}</span>
                </li>
            </ul>

            <button :disabled="loading" @click="pay" class="btn-aurora mt-8 w-full">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <CreditCard v-else class="h-4 w-4" />
                <span>{{ loading ? (isNl ? 'Bezig…' : 'Redirection…') : (isNl ? 'Betalen en doorgaan' : 'Payer et continuer') }}</span>
            </button>

            <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-400">
                <Lock class="h-3.5 w-3.5" /> {{ isNl ? 'Veilige betaling via Stripe' : 'Paiement sécurisé via Stripe' }}
            </p>

            <div v-if="error" class="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                <AlertCircle class="h-4 w-4 shrink-0" /> {{ error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { FileCheck2, Check, CheckCircle2, CreditCard, Lock, ArrowRight, AlertCircle } from "lucide-vue-next";

const { locale } = useI18n();
const router = useRouter();
const isNl = computed(() => locale.value === "nl");

const credits = ref(0);
const amount = ref(null);
const currency = ref("EUR");
const loading = ref(false);
const error = ref("");

const priceLabel = computed(() => {
    if (amount.value == null) return isNl.value ? "Paiement" : "Paiement";
    const sym = currency.value === "EUR" ? "€" : currency.value + " ";
    const n = Number.isInteger(amount.value) ? amount.value : amount.value.toFixed(2);
    return `${n} ${sym}`.trim();
});

const features = computed(() =>
    isNl.value
        ? [
              "Realtime KBO-ondernemingsopzoeking",
              "Veilige identiteitsverificatie (Veriff)",
              "Formulier I & II, identiteitsattest en notulen",
              "Documenten klaar om af te drukken en in te dienen",
          ]
        : [
              "Recherche d'entreprise BCE en temps réel",
              "Vérification d'identité sécurisée (Veriff)",
              "Formulaire I & II, attestation d'identité et PV",
              "Documents prêts à imprimer et à déposer",
          ]
);

onMounted(async () => {
    try {
        const { data } = await axiosInstance.get("/billing/subscription");
        credits.value = Number(data.credits || 0);
    } catch (e) { /* ignore */ }
    try {
        const { data } = await axiosInstance.get("/billing/dossier-price");
        amount.value = data.amount;
        currency.value = data.currency || "EUR";
    } catch (e) { /* ignore */ }
});

const startDemande = () => {
    sessionStorage.removeItem("demande_id");
    router.push({ name: "step1" });
};

const pay = async () => {
    error.value = "";
    loading.value = true;
    try {
        const { data } = await axiosInstance.post("/billing/purchase-one-dossier", { locale: locale.value });
        if (data?.checkout_url) {
            window.location.href = data.checkout_url;
            return;
        }
        error.value = isNl.value ? "Kon de betaling niet starten." : "Impossible de démarrer le paiement.";
    } catch (e) {
        error.value = e?.response?.data?.error || (isNl.value ? "Er ging iets mis." : "Une erreur est survenue.");
    } finally {
        loading.value = false;
    }
};
</script>
