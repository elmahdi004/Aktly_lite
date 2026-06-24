<template>
    <div v-if="visible">
        <div class="h-16 w-full"></div>
        <div class="top-ribbon fixed left-0 top-0 z-40 flex w-full justify-center px-3 py-3 sm:justify-end sm:px-6">
            <div class="animate-fade-up flex items-center gap-2.5">
                <!-- ============ user / settings dropdown ============ -->
                <div ref="menuRef" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex items-center gap-2.5 rounded-2xl border border-slate-200 bg-white/85 py-1.5 pl-1.5 pr-3 text-sm font-medium text-slate-700 shadow-sm backdrop-blur transition hover:border-emerald-300 hover:shadow-md"
                    >
                        <span class="brand-gradient grid h-8 w-8 place-items-center rounded-xl text-[13px] font-bold text-white">
                            {{ initial }}
                        </span>
                        <span class="hidden max-w-[10rem] truncate font-semibold text-slate-800 sm:block">
                            {{ auth.user?.name || 'Utilisateur' }}
                        </span>
                        <ChevronDown class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" />
                    </button>

                    <transition name="pop">
                        <div
                            v-if="open"
                            class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xl ring-1 ring-slate-900/5"
                        >
                            <!-- user header -->
                            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3.5">
                                <span class="brand-gradient grid h-10 w-10 place-items-center rounded-xl text-sm font-bold text-white">
                                    {{ initial }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth.user?.name || 'Utilisateur' }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ auth.user?.email || '' }}</p>
                                </div>
                            </div>

                            <!-- subscription / plan -->
                            <div class="border-b border-slate-100 px-4 py-3">
                                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                                    <CreditCard class="h-3.5 w-3.5 text-emerald-600" />
                                    <span>{{ $t('billing.plan') }}</span>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        {{ auth.user?.subscription_plan || '-' }}
                                    </span>
                                    <router-link
                                        :to="{ name: 'buyCredits' }"
                                        @click="open = false"
                                        class="text-xs font-semibold text-emerald-600 transition hover:text-emerald-700"
                                    >
                                        {{ $t('profile.manageSubscription') }}
                                    </router-link>
                                </div>
                            </div>

                            <!-- language switch -->
                            <div class="border-b border-slate-100 px-4 py-3">
                                <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500">
                                    <Globe class="h-3.5 w-3.5 text-emerald-600" />
                                    <span>{{ currentLang?.label }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        v-for="opt in langOptions"
                                        :key="opt.code"
                                        type="button"
                                        @click="changeLanguage(opt.code)"
                                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border py-2 text-sm font-medium transition"
                                        :class="locale === opt.code
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300'"
                                    >
                                        <span class="text-base leading-none">{{ opt.flag }}</span>
                                        <span class="text-xs">{{ opt.code.toUpperCase() }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- links -->
                            <div class="p-1.5">
                                <router-link
                                    :to="{ name: 'profile' }"
                                    @click="open = false"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-emerald-50/60 hover:text-emerald-700"
                                >
                                    <User class="h-[18px] w-[18px] text-slate-400" />
                                    {{ $t('dashboard.profile') }}
                                </router-link>
                                <router-link
                                    :to="{ name: 'changePassword' }"
                                    @click="open = false"
                                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-emerald-50/60 hover:text-emerald-700"
                                >
                                    <KeyRound class="h-[18px] w-[18px] text-slate-400" />
                                    Modifier le mot de passe
                                </router-link>
                                <button
                                    type="button"
                                    @click="logout()"
                                    class="mt-0.5 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                                >
                                    <LogOut class="h-[18px] w-[18px]" />
                                    {{ $t('dashboard.logout') }}
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.top-ribbon {
    left: 0;
    width: 100%;
}

@media (min-width: 768px) {
    .top-ribbon {
        left: var(--sidebar-width, 16rem);
        width: calc(100% - var(--sidebar-width, 16rem));
    }
}

.pop-enter-active,
.pop-leave-active {
    transition: opacity 0.16s ease, transform 0.16s ease;
}
.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(0.98);
}
</style>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import axiosInstance from "@/axios.js";
import { useAuthStore } from "@/pinia.js";
import { ChevronDown, User, Globe, LogOut, KeyRound, CreditCard } from "lucide-vue-next";

const { locale } = useI18n();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const publicRoutes = new Set([
    "login",
    "register",
    "forgotpassword",
    "password.reset",
    "idanalyzeruser",
    "verifyEmail",
    "paySuccess",
    "payCancel",
]);

const hiddenRoutes = new Set(["step1"]);

const ribbonRoutes = new Set([
    "step1",
    "step1Validate",
    "servicesStep",
    "step2",
    "step3",
    "step4",
    "step5",
    "step6",
    "addressService",
    "dirigeantsService",
    "cessionService",
    "client",
    "mesDemandes",
    "assistance",
    "createTicket",
    "admin",
    "adminServices",
    "adminSupport",
    "adminSettings",
    "adminUsers",
    "dashboard",
]);

const visible = computed(() => {
    const token = localStorage.getItem("token");
    return (Boolean(token) || Boolean(auth.user)) && !publicRoutes.has(route.name) && !hiddenRoutes.has(route.name);
});

const hasRibbon = computed(() => ribbonRoutes.has(route.name));

const initial = computed(() => {
    const name = auth.user?.name || "Utilisateur";
    return name.trim().charAt(0).toUpperCase() || "U";
});

const langOptions = [
    { code: "fr", label: "Français", flag: "🇫🇷" },
    { code: "nl", label: "Nederlands", flag: "🇳🇱" },
];

const currentLang = computed(() => {
    return langOptions.find((opt) => opt.code === locale.value) || langOptions[0];
});

const changeLanguage = async (lang) => {
    locale.value = lang;
    try {
        await axiosInstance.post("/user/language", { language: lang });
        localStorage.setItem("lang", lang);
    } catch (error) {
        console.error("Erreur lors de la sauvegarde de la langue :", error);
    }
};

const logout = async () => {
    await axiosInstance
        .post("/logout")
        .then(() => {
            localStorage.removeItem("token");
            router.push({ name: "login", params: { lang: locale.value } });
        })
        .catch((error) => {
            console.log(error);
        });
};

// --- UI-only state: custom dropdown open + click-outside ---
const open = ref(false);
const menuRef = ref(null);

const onClickOutside = (e) => {
    if (open.value && menuRef.value && !menuRef.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));
</script>
