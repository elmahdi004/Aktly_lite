<template>
    <AuthLayout
        :headline="locale === 'nl' ? 'Maak uw professionele account.' : 'Créez votre compte professionnel.'"
        :subtitle="locale === 'nl' ? 'Beheer zetelverplaatsingen van begin tot eind, met vertrouwen.' : 'Gérez les transferts de siège de bout en bout, en toute confiance.'"
    >
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900">{{ $t("register.title") }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $t("register.subtitle") }}</p>

        <form method="POST" @submit.prevent="register()" class="mt-7 space-y-4">
            <!-- salutation -->
            <div class="animate-fade-up-1">
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ $t("register.salutation") }}</label>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition"
                        :class="user.salutation === 'Madame' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                    >
                        <input type="radio" name="salutation" value="Madame" v-model="user.salutation" class="sr-only" />
                        {{ $t("register.salutationMadame") }}
                    </label>
                    <label
                        class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition"
                        :class="user.salutation === 'Monsieur' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                    >
                        <input type="radio" name="salutation" value="Monsieur" v-model="user.salutation" class="sr-only" />
                        {{ $t("register.salutationMonsieur") }}
                    </label>
                </div>
            </div>

            <!-- names -->
            <div class="grid grid-cols-2 gap-3 animate-fade-up-1">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("register.firstName") }}</label>
                    <input v-model="user.first_name" @input="inputError.first_name = ''" type="text" class="input-aurora" :class="inputError.first_name ? '!border-red-400' : ''" />
                    <InputError v-if="inputError.first_name" :error="inputError.first_name" class="mt-1" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("register.lastName") }}</label>
                    <input v-model="user.last_name" @input="inputError.last_name = ''" type="text" class="input-aurora" :class="inputError.last_name ? '!border-red-400' : ''" />
                    <InputError v-if="inputError.last_name" :error="inputError.last_name" class="mt-1" />
                </div>
            </div>

            <!-- phone -->
            <div class="animate-fade-up-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("register.phone") }}</label>
                <div class="relative">
                    <Phone class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input v-model="user.phone" type="text" class="input-aurora pl-11" :placeholder="$t('register.phonePlaceholder')" />
                </div>
            </div>

            <!-- email -->
            <div class="animate-fade-up-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("register.email") }}</label>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input v-model="user.email" @input="inputError.email = ''" type="text" class="input-aurora pl-11" :class="inputError.email ? '!border-red-400' : ''" />
                </div>
                <InputError v-if="inputError.email" :error="inputError.email" class="mt-1" />
            </div>

            <!-- password -->
            <div class="animate-fade-up-3">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("register.password") }}</label>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="user.password"
                        @input="inputError.password = ''"
                        :type="showPassword ? 'text' : 'password'"
                        class="input-aurora pl-11 pr-11"
                        :class="inputError.password ? '!border-red-400' : ''"
                    />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                        <component :is="showPassword ? EyeOff : Eye" class="h-[18px] w-[18px]" />
                    </button>
                </div>
                <InputError v-if="inputError.password" :error="inputError.password" class="mt-1" />
            </div>

            <p class="rounded-xl bg-slate-50 px-4 py-2.5 text-center text-xs text-slate-500">
                {{ $t("register.textItaa2") }}<b class="text-slate-700">support@legakte.be</b>
            </p>

            <div v-if="errormsg" class="rounded-xl bg-red-50 px-4 py-3 text-center text-sm font-medium text-red-600">{{ errormsg }}</div>

            <button :disabled="loading" type="submit" class="btn-aurora w-full">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? $t("register.loading") : $t("register.registerButton") }}</span>
                <ArrowRight v-if="!loading" class="h-4 w-4" />
            </button>

            <div v-if="emailVerificationSent" class="flex flex-col items-center gap-1 rounded-xl bg-emerald-50 px-4 py-3 text-center">
                <p class="text-sm font-medium text-emerald-700">{{ $t("register.emailVerificationSent") }}</p>
                <button type="button" @click="resendVerificationEmail" class="text-sm font-semibold text-emerald-600 underline">{{ $t("register.resendEmail") }}</button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            {{ $t("register.alreadyHaveAccount") }}
            <router-link :to="loginLink" class="font-semibold text-emerald-600 transition hover:text-emerald-700">{{ $t("register.login") }}</router-link>
        </p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "@/auth/AuthLayout.vue";
import InputError from "@/Components/ui/InputError.vue";
import { ref, computed } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { Mail, Lock, Phone, Eye, EyeOff, ArrowRight } from "lucide-vue-next";

const { t, locale } = useI18n();

const loginLink = computed(() => ({ name: "login", params: { lang: locale.value } }));
const errormsg = ref("");
const loading = ref(false);
const showPassword = ref(false);
const user = ref({ first_name: "", last_name: "", email: "", password: "", salutation: "", phone: "" });
const inputError = ref({ first_name: "", last_name: "", email: "", password: "", message: "" });
const emailVerificationSent = ref(false);
const router = useRouter();

const register = async () => {
    loading.value = true;
    inputError.value.message = "";
    await axiosInstance
        .post("/register", {
            first_name: user.value.first_name,
            last_name: user.value.last_name,
            email: user.value.email,
            password: user.value.password,
            langue_user: locale.value,
            salutation: user.value.salutation,
            phone: user.value.phone,
        })
        .then(({ data }) => {
            localStorage.setItem("token", data.token);
            if (data.email_verification_sent) emailVerificationSent.value = true;
            router.push({ name: "buyCredits" });
        })
        .catch((error) => {
            const errs = error?.response?.data?.errors;
            if (error?.response?.data?.message && !errs) {
                errormsg.value = t("register.errorMessage");
            } else if (errs) {
                inputError.value.first_name = errs.first_name ? errs.first_name[0] : "";
                inputError.value.last_name = errs.last_name ? errs.last_name[0] : "";
                inputError.value.email = errs.email ? errs.email[0] : "";
                inputError.value.password = errs.password ? errs.password[0] : "";
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

const resendVerificationEmail = async () => {
    await axiosInstance.post("/send-verification-link", { email: user.value.email });
};
</script>
