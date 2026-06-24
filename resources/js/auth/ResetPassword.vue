<template>
    <AuthLayout
        :headline="locale === 'nl' ? 'Kies een nieuw wachtwoord.' : 'Choisissez un nouveau mot de passe.'"
        :subtitle="locale === 'nl' ? 'Bijna klaar — stel een sterk wachtwoord in om uw account te beveiligen.' : 'Dernière étape — définissez un mot de passe fort pour sécuriser votre compte.'"
    >
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900">{{ $t("auth.reset_title") }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $t("auth.reset_subtitle") }}</p>

        <form @submit.prevent="resetPassword" class="mt-8 space-y-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("login.email") }}</label>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input v-model="email" type="email" :placeholder="$t('auth.reset_email')" class="input-aurora pl-11" :class="errors.email ? '!border-red-400' : ''" />
                </div>
                <p v-if="errors.email" class="mt-1 text-xs font-medium text-red-500">{{ errors.email }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("auth.reset_password") }}</label>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input v-model="password" :type="showPassword ? 'text' : 'password'" :placeholder="$t('auth.reset_password')" class="input-aurora pl-11 pr-11" :class="errors.password ? '!border-red-400' : ''" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                        <component :is="showPassword ? EyeOff : Eye" class="h-[18px] w-[18px]" />
                    </button>
                </div>
                <p v-if="errors.password" class="mt-1 text-xs font-medium text-red-500">{{ errors.password }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("auth.reset_confirm") }}</label>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input v-model="password_confirmation" :type="showPassword ? 'text' : 'password'" :placeholder="$t('auth.reset_confirm')" class="input-aurora pl-11" :class="errors.password_confirmation ? '!border-red-400' : ''" />
                </div>
                <p v-if="errors.password_confirmation" class="mt-1 text-xs font-medium text-red-500">{{ errors.password_confirmation }}</p>
            </div>

            <div v-if="message" class="flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium" :class="isError ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'">
                <component :is="isError ? AlertCircle : CheckCircle2" class="h-4 w-4 shrink-0" />
                {{ message }}
            </div>

            <button :disabled="loading" type="submit" class="btn-aurora w-full">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? $t("login.loading") : $t("auth.reset_button") }}</span>
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
import { useRouter } from "vue-router";
import { Mail, Lock, Eye, EyeOff, ArrowRight, ArrowLeft, CheckCircle2, AlertCircle } from "lucide-vue-next";

const { t, locale } = useI18n();
const router = useRouter();

const email = ref(new URLSearchParams(window.location.search).get("email") || "");
const pathSegments = window.location.pathname.split("/");
const token = ref(pathSegments[pathSegments.length - 1]);
const password = ref("");
const password_confirmation = ref("");
const message = ref("");
const isError = ref(false);
const loading = ref(false);
const showPassword = ref(false);

const errors = ref({ email: "", password: "", password_confirmation: "" });

const validateForm = () => {
    errors.value = { email: "", password: "", password_confirmation: "" };
    let valid = true;
    if (!email.value) {
        errors.value.email = t("auth.error_email_required");
        valid = false;
    }
    if (!password.value) {
        errors.value.password = t("auth.error_password_required");
        valid = false;
    }
    if (!password_confirmation.value) {
        errors.value.password_confirmation = t("auth.error_confirm_required");
        valid = false;
    }
    if (password.value && password_confirmation.value && password.value !== password_confirmation.value) {
        errors.value.password_confirmation = t("auth.error_password_match");
        valid = false;
    }
    return valid;
};

const resetPassword = async () => {
    if (!validateForm()) return;
    loading.value = true;
    message.value = "";
    try {
        await axiosInstance.post("/reset-password", {
            email: email.value,
            token: token.value,
            password: password.value,
            password_confirmation: password_confirmation.value,
        });
        isError.value = false;
        message.value = t("auth.reset_success");
        setTimeout(() => router.push({ name: "login", params: { lang: locale.value } }), 900);
    } catch (err) {
        isError.value = true;
        message.value = t("auth.reset_error");
    } finally {
        loading.value = false;
    }
};
</script>
