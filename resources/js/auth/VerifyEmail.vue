<template>
    <AuthLayout
        :headline="isNl ? 'Nog één stap.' : 'Plus qu’une étape.'"
        :subtitle="isNl ? 'Bevestig uw e-mailadres om uw Aktly Lite-account te activeren.' : 'Confirmez votre adresse e-mail pour activer votre compte Aktly Lite.'"
    >
        <div class="text-center">
            <!-- icon -->
            <div
                class="mx-auto grid h-16 w-16 place-items-center rounded-2xl transition-colors"
                :class="success ? 'bg-emerald-100 text-emerald-600' : error ? 'bg-red-100 text-red-500' : 'brand-gradient text-white'"
            >
                <CheckCircle2 v-if="success" class="h-8 w-8" />
                <XCircle v-else-if="error" class="h-8 w-8" />
                <Mail v-else class="h-8 w-8" />
            </div>

            <h1 class="mt-5 font-display text-2xl font-extrabold tracking-tight text-slate-900">
                {{ isNl ? 'E-mail bevestigen' : 'Confirmer votre e-mail' }}
            </h1>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                {{ isNl ? 'Klik op de knop hieronder om de bevestiging van uw e-mailadres af te ronden.' : 'Cliquez sur le bouton ci-dessous pour finaliser la confirmation de votre adresse e-mail.' }}
            </p>

            <!-- messages -->
            <transition name="slide-fade">
                <div v-if="success" class="mt-5 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    <CheckCircle2 class="h-4 w-4 shrink-0" />
                    <span>{{ isNl ? 'Uw e-mailadres is bevestigd. U wordt doorgestuurd…' : 'Votre e-mail est confirmé. Redirection en cours…' }}</span>
                </div>
            </transition>
            <transition name="slide-fade">
                <div v-if="error" class="mt-5 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                    <AlertCircle class="h-4 w-4 shrink-0" />
                    <span>{{ error }}</span>
                </div>
            </transition>

            <!-- verify button -->
            <button :disabled="verifying || success" @click="verifyEmail" class="btn-aurora mt-7 w-full">
                <span v-if="verifying" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <ShieldCheck v-else class="h-4 w-4" />
                <span>{{ verifying ? (isNl ? 'Bezig…' : 'Vérification…') : (isNl ? 'Mijn e-mail bevestigen' : 'Confirmer mon e-mail') }}</span>
            </button>

            <p class="mt-6 border-t border-slate-100 pt-5 text-sm text-slate-400">
                <router-link :to="{ name: 'login', params: { lang: locale } }" class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 transition hover:text-emerald-700">
                    <ArrowLeft class="h-4 w-4" />
                    {{ isNl ? 'Terug naar inloggen' : 'Retour à la connexion' }}
                </router-link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import axiosInstance from "@/axios.js";
import AuthLayout from "./AuthLayout.vue";
import { Mail, CheckCircle2, XCircle, AlertCircle, ShieldCheck, ArrowLeft } from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const { locale } = useI18n();
const isNl = computed(() => locale.value === "nl");

const verifying = ref(false);
const success = ref(false);
const error = ref("");

const token = route.params.token;

const verifyEmail = async () => {
    verifying.value = true;
    error.value = "";
    try {
        await axiosInstance.get(`/email/verify/${token}`);
        success.value = true;
        setTimeout(() => router.push({ name: "login", params: { lang: locale.value } }), 1800);
    } catch (e) {
        error.value =
            e?.response?.data?.message ||
            (isNl.value ? "Verificatie mislukt. De link is mogelijk verlopen." : "Échec de la vérification. Le lien a peut-être expiré.");
    } finally {
        verifying.value = false;
    }
};
</script>

<style scoped>
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-6px);
    opacity: 0;
}
</style>
