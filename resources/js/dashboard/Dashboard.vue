<template>
    <div class="aurora-wrap min-h-screen w-full bg-slate-50" :style="sidebarStyleVars">
        <!-- soft aurora accents -->
        <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
            <div class="aurora-blob" style="width:460px;height:460px;background:#a7f3d0;top:-200px;left:-120px;opacity:.18;"></div>
            <div class="aurora-blob" style="width:400px;height:400px;background:#6ee7b7;bottom:-220px;right:-140px;opacity:.14;"></div>
        </div>

        <!-- ============ mobile drawer backdrop ============ -->
        <transition name="fade">
            <div
                v-if="mobileOpen"
                @click="mobileOpen = false"
                class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm md:hidden"
            ></div>
        </transition>

        <!-- ============ SIDEBAR (glass) ============ -->
        <aside
            class="glass fixed top-0 left-0 z-50 flex h-screen w-[280px] flex-col overflow-y-auto border-r border-white/60 px-4 py-5 transition-transform duration-300 md:w-[var(--sidebar-width)] md:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- brand -->
            <div class="mb-7 flex items-center justify-between px-1">
                <router-link :to="{ name: isAdmin ? 'admin' : 'client' }" class="flex items-center gap-2.5" @click="mobileOpen = false">
                    <div class="brand-gradient grid h-9 w-9 place-items-center rounded-xl">
                        <FileCheck2 class="h-5 w-5 text-white" />
                    </div>
                    <span class="font-display text-lg font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
                </router-link>
                <button
                    type="button"
                    @click="mobileOpen = false"
                    class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 md:hidden"
                    aria-label="Close menu"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- nav -->
            <nav class="flex flex-1 flex-col gap-1" @click="mobileOpen = false">
                <DashboardItem v-if="isAdmin" :title="$t('dashboard.dashboard')" :active="true" pathName="admin">
                    <LayoutDashboard class="h-[18px] w-[18px]" />
                </DashboardItem>
                <DashboardItem v-if="!isAdmin" :title="$t('dashboard.dashboard')" :active="true" pathName="client">
                    <LayoutDashboard class="h-[18px] w-[18px]" />
                </DashboardItem>

                <DashboardItem v-if="isAdmin" :title="$t('dashboard.request')" :active="false" pathName="adminServices">
                    <ClipboardList class="h-[18px] w-[18px]" />
                </DashboardItem>
                <DashboardItem v-if="isAdmin" :title="$t('dashboard.users')" :active="false" pathName="adminUsers">
                    <Users class="h-[18px] w-[18px]" />
                </DashboardItem>
                <DashboardItem v-if="isAdmin" :title="$t('dashboard.coupons')" :active="false" pathName="adminCoupons">
                    <Ticket class="h-[18px] w-[18px]" />
                </DashboardItem>
                <DashboardItem v-if="isAdmin" :title="$t('dashboard.support')" :active="false" pathName="adminSupport">
                    <MessageSquare class="h-[18px] w-[18px]" />
                </DashboardItem>

                <DashboardItem v-if="!isAdmin" :title="$t('dashboard.support')" :active="false" pathName="createTicket">
                    <LifeBuoy class="h-[18px] w-[18px]" />
                </DashboardItem>
                <DashboardItem v-if="!isAdmin" :title="$t('dashboard.claims')" :active="false" pathName="assistance">
                    <MessageSquare class="h-[18px] w-[18px]" />
                </DashboardItem>
                <!-- primary CTA -->
                <router-link
                    :to="{ name: 'step1' }"
                    @click="onAddDemande"
                    class="btn-aurora mt-3 w-full"
                >
                    <Plus class="h-4 w-4" />
                    <span class="truncate">{{ $t('dashboard.addDemande') }}</span>
                </router-link>
            </nav>

            <!-- footer : user + logout -->
            <div class="mt-4 space-y-1 border-t border-slate-200/70 pt-3">
                <router-link
                    :to="{ name: 'profile' }"
                    @click="mobileOpen = false"
                    class="flex items-center gap-3 rounded-xl px-2.5 py-2 transition hover:bg-slate-100/70"
                >
                    <div class="brand-gradient grid h-9 w-9 shrink-0 place-items-center rounded-full text-sm font-bold text-white">{{ userInitial }}</div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ userName }}</p>
                        <p class="truncate text-xs text-slate-400">{{ userEmail }}</p>
                    </div>
                </router-link>
                <button
                    type="button"
                    @click="logout"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                >
                    <LogOut class="h-[18px] w-[18px]" />
                    <span>{{ $t('dashboard.logout') }}</span>
                </button>
            </div>
        </aside>

        <!-- ============ MAIN COLUMN ============ -->
        <div class="flex min-h-screen flex-col md:ml-[var(--sidebar-width)]">
            <!-- top bar (mobile menu toggle + TopRightBar slot) -->
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/70 bg-white/70 px-4 backdrop-blur md:px-6">
                <button
                    type="button"
                    @click="mobileOpen = true"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700 md:hidden"
                    aria-label="Open menu"
                >
                    <Menu class="h-5 w-5" />
                </button>
                <router-link :to="{ name: isAdmin ? 'admin' : 'client' }" class="flex items-center gap-2 md:hidden">
                    <div class="brand-gradient grid h-8 w-8 place-items-center rounded-lg">
                        <FileCheck2 class="h-4 w-4 text-white" />
                    </div>
                    <span class="font-display text-base font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
                </router-link>
                <div class="ml-auto">
                    <TopRightBar />
                </div>
            </header>

            <!-- content -->
            <main class="relative z-10 flex-1 p-4 sm:p-6">
                <div class="mx-auto w-full max-w-6xl animate-fade-up">
                    <router-view></router-view>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import DashboardItem from "./components/DashboardItem.vue";
import TopRightBar from "@/components/TopRightBar.vue";
import { computed, onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/pinia.js';
import { useI18n } from 'vue-i18n';
import axiosInstance from "@/axios.js";
import { useRouter, useRoute } from "vue-router";
import { FileCheck2, X, Menu, Plus, LogOut, LayoutDashboard, ClipboardList, Users, Ticket, MessageSquare, LifeBuoy, CreditCard } from "lucide-vue-next";

const router = useRouter();
const route = useRoute();

const { locale } = useI18n();

const auth = useAuthStore()
const demandeId = ref(sessionStorage.getItem("demande_id") || "");
const maxSidebarWidth = 264;
const sidebarWidth = ref(maxSidebarWidth);
const sidebarWide = computed(() => true);
const sidebarStyleVars = computed(() => ({ '--sidebar-width': `${sidebarWidth.value}px` }));
const hasValidCoupon = ref(null);
const mobileOpen = ref(false);

const userName = computed(() => auth.user?.name || 'Utilisateur');
const userEmail = computed(() => auth.user?.email || '');
const userInitial = computed(() => (auth.user?.name || 'U').trim().charAt(0).toUpperCase());

const updateGlobalSidebarWidth = (value) => {
    document.documentElement.style.setProperty('--sidebar-width', `${value}px`);
};

const onAddDemande = () => {
    sessionStorage.removeItem("demande_id");
    mobileOpen.value = false;
};

onMounted(async () => {
    updateGlobalSidebarWidth(sidebarWidth.value);
    if (!auth.user) {
        await auth.fetchUser()
    }
    await checkCouponStatus();
})

watch(sidebarWidth, (value) => {
    updateGlobalSidebarWidth(value);
});

const checkCouponStatus = async () => {
    try {
        const { data } = await axiosInstance.get("/profile");
        const appliedCouponCode = data.applied_coupon_code;
        const couponExpiresAt = data.coupon_expires_at;
        const couponDossiersRemaining = data.coupon_dossiers_remaining;

        const isCouponExpired = couponExpiresAt ? new Date(couponExpiresAt) < new Date() : true;
        hasValidCoupon.value = appliedCouponCode && couponExpiresAt && !isCouponExpired && couponDossiersRemaining > 0;
    } catch (error) {
        console.error("Error checking coupon status:", error);
        hasValidCoupon.value = false;
    }
}

const isAdmin = computed(() => auth.isAdmin)
watch(
    () => route.fullPath,
    () => {
        demandeId.value = sessionStorage.getItem("demande_id") || "";
    }
);
const changeLanguage = async (lang) => {
    locale.value = lang; // Changer la langue dans l'application

    try {
        // Sauvegarder la langue dans la base de données
        await saveLanguage(lang);
    } catch (error) {
        console.error("Erreur lors de la sauvegarde de la langue :", error);
    }
};

const logout = async () => {
    const response = await axiosInstance
        .post("/logout")
        .then(({ data }) => {
            console.log(locale.value);
            localStorage.removeItem("token");
            router.push({ name: "login", params: { lang: locale.value } });
        })
        .catch((error) => {
            console.log(error);
        })
        .finally(() => {});
    // this.$router.push('/steps');
};

const saveLanguage = async (lang) => {
    try {
        await axiosInstance.post("/user/language", { language: lang });
        localStorage.setItem("lang", lang);
        console.log("Langue sauvegardée avec succès !");
    } catch (error) {
        console.error("Erreur lors de la sauvegarde de la langue :", error);
    }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
