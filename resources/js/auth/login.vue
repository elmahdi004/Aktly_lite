<template>
    <AuthLayout>
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900">{{ $t("login.title") }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $t("login.subtitle") }}</p>

        <form @submit.prevent="login()" method="POST" class="mt-8 space-y-5">
            <div class="animate-fade-up-1">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("login.email") }}</label>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        @input="inputError.email = ''"
                        v-model="user.email"
                        :placeholder="$t('login.email')"
                        type="text"
                        class="input-aurora pl-11"
                        :class="inputError.email ? '!border-red-400 focus:!ring-red-400/15' : ''"
                    />
                </div>
                <InputError v-if="inputError.email" :error="inputError.email" class="mt-1" />
            </div>

            <div class="animate-fade-up-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $t("login.password") }}</label>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        @input="inputError.password = ''"
                        v-model="user.password"
                        :placeholder="$t('login.password')"
                        :type="showPassword ? 'text' : 'password'"
                        class="input-aurora pl-11 pr-11"
                        :class="inputError.password ? '!border-red-400 focus:!ring-red-400/15' : ''"
                    />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                        <component :is="showPassword ? EyeOff : Eye" class="h-[18px] w-[18px]" />
                    </button>
                </div>
                <InputError v-if="inputError.password" :error="inputError.password" class="mt-1" />
            </div>

            <div class="-mt-1 flex justify-end">
                <router-link :to="{ name: 'forgotpassword' }" class="text-sm font-medium text-emerald-600 transition hover:text-emerald-700">
                    {{ $t("auth.reset_title") }}
                </router-link>
            </div>

            <div v-if="inputError.message" class="rounded-xl bg-red-50 px-4 py-3 text-center text-sm font-medium text-red-600">
                {{ inputError.message }}
            </div>

            <button :disabled="loading" type="submit" class="btn-aurora w-full">
                <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <span>{{ loading ? $t("login.loading") : $t("login.loginButton") }}</span>
                <ArrowRight v-if="!loading" class="h-4 w-4" />
            </button>
        </form>

        <p class="mt-7 text-center text-sm text-slate-500">
            {{ $t("login.noAccount") }}
            <router-link :to="registerLink" class="font-semibold text-emerald-600 transition hover:text-emerald-700">
                {{ $t("login.register") }}
            </router-link>
        </p>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "@/auth/AuthLayout.vue";
import InputError from "@/Components/ui/InputError.vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { ref, computed } from "vue";
import { useAuthStore } from "@/pinia.js";
import { Mail, Lock, Eye, EyeOff, ArrowRight } from "lucide-vue-next";

const { t, locale } = useI18n();

const registerLink = computed(() => ({ name: "register", params: { lang: locale.value } }));

const user = ref({ email: "", password: "" });
const inputError = ref({ email: "", password: "", message: "" });
const loading = ref(false);
const showPassword = ref(false);
const router = useRouter();
const auth = useAuthStore();

const login = async () => {
    loading.value = true;
    inputError.value.message = "";
    await axiosInstance
        .post("/login", {
            email: user.value.email,
            password: user.value.password,
            langue_user: locale.value,
        })
        .then(({ data }) => {
            localStorage.setItem("token", data.token);
            if (data.email_verified_at === null || data.email_verified_at === undefined) {
                localStorage.removeItem("token");
                inputError.value.message = t("login.emailNotVerified");
                router.push({ name: "email-verification", params: { email: user.value.email } });
                return;
            }
            auth.checkAuthStatus()
                .then(() => router.push({ name: auth.isAdmin ? "admin" : "client" }))
                .catch(() => router.push({ name: "client" }));
        })
        .catch((error) => {
            const response = error?.response?.data || null;
            if (response?.message) {
                inputError.value.message = t("login.errorMessage");
            } else {
                inputError.value.email = response?.errors?.email ? response.errors.email[0] : "";
                inputError.value.password = response?.errors?.password ? response.errors.password[0] : "";
            }
        })
        .finally(() => {
            loading.value = false;
        });
};
</script>
