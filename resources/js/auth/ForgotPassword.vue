<template>
    <AuthLayout
        :headline="locale === 'nl' ? 'Wachtwoord vergeten?' : 'Mot de passe oublié ?'"
        :subtitle="locale === 'nl' ? 'Geen zorgen — we sturen u een veilige herstellink.' : 'Pas de panique — nous vous envoyons un lien de réinitialisation sécurisé.'"
    >
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900">{{ $t("auth.reset_title") }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $t("auth.forgot_subtitle") }}</p>

        <form @submit.prevent="sendResetLink" class="mt-8 space-y-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("login.email") }}</label>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="email"
                        type="email"
                        :placeholder="$t('auth.forgot_placeholder')"
                        class="input-aurora pl-11"
                        :class="isError ? '!border-red-400 focus:!ring-red-400/15' : ''"
                    />
                </div>
            </div>

            <div v-if="message" class="flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium" :class="isError ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'">
                <component :is="isError ? AlertCircle : CheckCircle2" class="h-4 w-4 shrink-0" />
                {{ message }}
            </div>

            <button :disabled="loading" type="submit" class="btn-aurora w-full">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? $t("login.loading") : $t("auth.forgot_button") }}</span>
                <ArrowRight v-if="!loading" class="h-4 w-4" />
            </button>
        </form>

        <router-link :to="{ name: 'login', params: { lang: locale } }" class="mt-7 flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-emerald-700">
            <ArrowLeft class="h-4 w-4" /> {{ $t("register.login") }}
        </router-link>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "@/auth/AuthLayout.vue";
import { ref } from "vue";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";
import { Mail, ArrowRight, ArrowLeft, CheckCircle2, AlertCircle } from "lucide-vue-next";

const { t, locale } = useI18n();

const email = ref("");
const message = ref("");
const isError = ref(false);
const loading = ref(false);

const sendResetLink = async () => {
    loading.value = true;
    message.value = "";
    try {
        await axiosInstance.post("/forgot-password", { email: email.value, locale: locale.value });
        isError.value = false;
        message.value = t("auth.forgot_success");
    } catch (err) {
        isError.value = true;
        message.value = t("auth.forgot_error");
    } finally {
        loading.value = false;
    }
};
</script>
