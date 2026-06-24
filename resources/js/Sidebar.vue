<template>
    <!-- mobile top bar : wordmark + hamburger -->
    <div class="sticky top-0 z-40 flex items-center justify-between border-b border-white/60 bg-white/70 px-4 py-3 backdrop-blur-md md:hidden">
        <router-link :to="{ name: isAdmin ? 'admin' : 'client' }" class="flex items-center gap-2.5">
            <div class="brand-gradient grid h-9 w-9 place-items-center rounded-xl">
                <FileCheck2 class="h-5 w-5 text-white" />
            </div>
            <span class="font-display text-base font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
        </router-link>
        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700"
            :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
        >
            <component :is="mobileOpen ? X : Menu" class="h-5 w-5" />
        </button>
    </div>

    <!-- mobile backdrop -->
    <transition name="fade">
        <div v-if="mobileOpen" @click="mobileOpen = false" class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-sm md:hidden"></div>
    </transition>

    <!-- the sidebar shell -->
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85%] flex-col border-r border-white/60 bg-white/70 backdrop-blur-xl transition-transform duration-300 md:w-full md:max-w-none md:translate-x-0"
        :class="mobileOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:shadow-none'"
    >
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:240px;height:240px;background:#6ee7b7;top:-120px;left:-80px;opacity:.18;"></div>
            <div class="aurora-blob" style="width:220px;height:220px;background:#a7f3d0;bottom:-120px;right:-90px;opacity:.14;"></div>
        </div>

        <!-- wordmark -->
        <div class="relative z-10 flex items-center justify-between px-5 pb-2 pt-6">
            <router-link :to="{ name: isAdmin ? 'admin' : 'client' }" @click="mobileOpen = false" class="flex items-center gap-2.5">
                <div class="brand-gradient grid h-10 w-10 place-items-center rounded-2xl">
                    <FileCheck2 class="h-5 w-5 text-white" />
                </div>
                <span class="font-display text-lg font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
            </router-link>
            <button
                type="button"
                @click="mobileOpen = false"
                class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-emerald-300 hover:text-emerald-700 md:hidden"
                aria-label="Close menu"
            >
                <X class="h-4.5 w-4.5" />
            </button>
        </div>

        <!-- nav -->
        <nav class="relative z-10 flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <!-- ===== Admin nav ===== -->
            <template v-if="isAdmin">
                <NavItem :to="{ name: 'admin' }" :label="$t('dashboard.dashboard')" :icon="LayoutDashboard" exact @navigate="mobileOpen = false" class="animate-fade-up-1" />
                <NavItem :to="{ name: 'adminServices' }" :label="$t('dashboard.request')" :icon="FolderKanban" @navigate="mobileOpen = false" class="animate-fade-up-1" />
                <NavItem :to="{ name: 'adminUsers' }" :label="$t('dashboard.users')" :icon="Users" @navigate="mobileOpen = false" class="animate-fade-up-2" />
                <NavItem :to="{ name: 'adminSupport' }" :label="$t('dashboard.support')" :icon="LifeBuoy" @navigate="mobileOpen = false" class="animate-fade-up-2" />
            </template>

            <!-- ===== Client nav ===== -->
            <template v-else>
                <NavItem :to="{ name: 'client' }" :label="$t('dashboard.dashboard')" :icon="LayoutDashboard" exact @navigate="mobileOpen = false" class="animate-fade-up-1" />
                <NavItem :to="{ name: 'step1' }" :label="$t('dashboard.addDemande')" :icon="FilePlus2" @navigate="onNewRequest" class="animate-fade-up-1" />
                <NavItem :to="{ name: 'createTicket' }" :label="$t('dashboard.support')" :icon="LifeBuoy" @navigate="mobileOpen = false" class="animate-fade-up-2" />
                <NavItem :to="{ name: 'assistance' }" :label="$t('dashboard.claims')" :icon="MessageSquareText" @navigate="mobileOpen = false" class="animate-fade-up-2" />
                <NavItem :to="{ name: 'profile' }" :label="$t('dashboard.profile')" :icon="UserRound" @navigate="mobileOpen = false" class="animate-fade-up-3" />
            </template>
        </nav>

        <!-- footer : product tours + logout -->
        <div class="relative z-10 space-y-3 border-t border-slate-100/80 px-4 py-4">
            <button
                @click="openProductTours"
                :disabled="isLoadingTours"
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700 disabled:opacity-60"
            >
                <span v-if="isLoadingTours" class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
                <Sparkles v-else class="h-4 w-4 text-emerald-600" />
                <span>{{ isLoadingTours ? ($t('sidebar.loading') || 'Loading...') : ($t('sidebar.productTours') || 'Interactive Tours') }}</span>
            </button>

            <button
                @click="logout"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                <LogOut class="h-4 w-4" />
                <span>{{ $t('dashboard.logout') }}</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { useRouter, RouterLink } from "vue-router";
import axiosInstance from "./axios";
import { useI18n } from 'vue-i18n';
import { onMounted, ref, watch, computed, h } from "vue";
import { useAuthStore } from "@/pinia.js";
import {
    FileCheck2,
    Menu,
    X,
    LayoutDashboard,
    FolderKanban,
    Users,
    LifeBuoy,
    FilePlus2,
    MessageSquareText,
    UserRound,
    Sparkles,
    LogOut,
    ChevronRight,
} from "lucide-vue-next";

const { locale } = useI18n();
const router = useRouter();
const authStore = useAuthStore();
const isAuthenticated = ref(false);
const isLoadingTours = ref(false);
const mobileOpen = ref(false);

const isAdmin = computed(() => authStore.isAdmin);

onMounted(() => {
    auth();
});

const auth = ()=> {
    const token = localStorage.getItem("token");
    if (token) {
        return isAuthenticated.value = true;
    }
    return isAuthenticated.value = false;
};
watch(
  () => router.currentRoute.value,
  () => {
    auth();
    mobileOpen.value = false;
  }
);

const logout = async () => {
    console.log("logout");
    const response = await axiosInstance
        .post("/logout")
        .then(({ data }) => {
            console.log("I am in");
            localStorage.removeItem("token");
            router.push({ name: "login",  query: { lang: locale.value } });
        })
        .catch((error) => {
            console.log(error);
        })
        .finally(() => {});
    // this.$router.push('/steps');
};

const openProductTours = () => {
    const demoId = locale.value === 'nl' ? 'cmikkhan00001z50ijybidjl0' : 'cmikk2jho02bqx20ib4tjyq3r';
    if (window.Supademo) {
        isLoadingTours.value = true;
        window.Supademo.widget.open(demoId);
        // Hide loading after a short delay to ensure widget has started loading
        setTimeout(() => {
            isLoadingTours.value = false;
        }, 1000);
    }
};

// "Nouvelle demande" must reset any in-progress dossier (parity with the old
// DashboardItem step1 behaviour) before navigating.
const onNewRequest = () => {
    sessionStorage.removeItem("demande_id");
    mobileOpen.value = false;
};

// Inline nav item: emerald active rail + lucide icon, aurora glass hover.
const NavItem = {
    props: {
        to: { type: Object, required: true },
        label: { type: String, required: true },
        icon: { type: [Object, Function], required: true },
        exact: { type: Boolean, default: false },
    },
    emits: ["navigate"],
    setup(props, { emit }) {
        return () =>
            h(
                RouterLink,
                {
                    to: props.to,
                    "active-class": props.exact ? "" : "is-active",
                    "exact-active-class": props.exact ? "is-active" : "",
                    class: "nav-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition-all hover:bg-emerald-50/60 hover:text-emerald-700",
                    onClick: () => emit("navigate"),
                },
                {
                    default: () => [
                        h("span", { class: "nav-rail" }),
                        h(props.icon, { class: "h-[18px] w-[18px] shrink-0 text-slate-400 transition-colors group-hover:text-emerald-600" }),
                        h("span", { class: "flex-1 truncate" }, props.label),
                        h(ChevronRight, { class: "h-4 w-4 shrink-0 text-slate-300 opacity-0 transition-opacity group-hover:opacity-100" }),
                    ],
                }
            );
    },
};
</script>

<style scoped>
/* emerald active rail + active surface for nav items */
.nav-rail {
    position: absolute;
    left: 0;
    top: 50%;
    height: 0;
    width: 3px;
    transform: translateY(-50%);
    border-radius: 9999px;
    background: linear-gradient(180deg, #059669 0%, #10b981 100%);
    transition: height 0.2s ease;
}
.nav-item.is-active {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
    font-weight: 600;
}
.nav-item.is-active .nav-rail {
    height: 60%;
}
.nav-item.is-active :deep(svg:first-of-type) {
    color: #059669;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
