<template>
    <div class="aurora-wrap min-h-screen w-full bg-slate-50 lg:flex">
        <!-- ============ LEFT : aurora brand panel ============ -->
        <aside class="relative hidden flex-col justify-between overflow-hidden p-12 text-white lg:flex lg:min-h-screen lg:w-[52%]">
            <div class="absolute inset-0" style="background: linear-gradient(150deg,#064e3b 0%,#059669 50%,#10b981 100%);"></div>
            <div class="aurora-blob" style="width:380px;height:380px;background:#6ee7b7;top:-70px;left:-50px;"></div>
            <div class="aurora-blob" style="width:300px;height:300px;background:#34d399;bottom:-50px;right:-40px;"></div>
            <div class="aurora-blob" style="width:220px;height:220px;background:#a7f3d0;top:42%;left:48%;opacity:.4;"></div>

            <div class="relative z-10 flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/30 backdrop-blur-md">
                    <FileCheck2 class="h-6 w-6 text-white" />
                </div>
                <span class="font-display text-xl font-extrabold tracking-tight">Aktly<span class="font-medium text-emerald-100"> Lite</span></span>
            </div>

            <div class="relative z-10 max-w-md">
                <h2 class="font-display text-[2.6rem] font-extrabold leading-[1.1]">{{ displayHeadline }}</h2>
                <p class="mt-5 text-[15px] leading-relaxed text-emerald-50/90">{{ displaySubtitle }}</p>
                <ul class="mt-9 space-y-4">
                    <li v-for="(f, i) in displayFeatures" :key="i" class="flex items-center gap-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white/15 ring-1 ring-white/25">
                            <Check class="h-4 w-4 text-white" />
                        </span>
                        <span class="text-[15px] text-emerald-50/95">{{ f }}</span>
                    </li>
                </ul>
            </div>

            <div class="relative z-10 text-xs text-emerald-100/70">© 2026 Aktly Lite · Belgique</div>
        </aside>

        <!-- ============ RIGHT : content ============ -->
        <main class="relative flex min-h-screen flex-1 items-center justify-center p-6 sm:p-10">
            <div class="pointer-events-none absolute inset-0 overflow-hidden lg:hidden">
                <div class="aurora-blob" style="width:280px;height:280px;background:#6ee7b7;top:-90px;right:-70px;opacity:.3;"></div>
            </div>

            <!-- language switcher -->
            <div class="absolute right-6 top-6 z-30">
                <div class="relative">
                    <button
                        type="button"
                        @click="showLangMenu = !showLangMenu"
                        class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2 text-sm font-medium text-slate-600 backdrop-blur transition hover:border-emerald-300 hover:text-slate-800"
                    >
                        <Globe class="h-4 w-4 text-emerald-600" />
                        {{ currentLanguage }}
                        <ChevronDown class="h-4 w-4 transition-transform" :class="showLangMenu ? 'rotate-180' : ''" />
                    </button>
                    <transition name="fade">
                        <div v-if="showLangMenu" class="absolute right-0 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-900/5">
                            <button @click="changeLanguage('fr')" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">🇫🇷 Français</button>
                            <button @click="changeLanguage('nl')" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">🇳🇱 Nederlands</button>
                        </div>
                    </transition>
                </div>
            </div>

            <div class="relative z-10 w-full" :class="wide ? 'max-w-xl' : 'max-w-md'">
                <!-- mobile brand -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="brand-gradient grid h-10 w-10 place-items-center rounded-2xl">
                        <FileCheck2 class="h-5 w-5 text-white" />
                    </div>
                    <span class="font-display text-lg font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
                </div>

                <div class="glass-card animate-fade-up p-8 sm:p-10">
                    <slot />
                </div>

                <slot name="footer" />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import { FileCheck2, Check, Globe, ChevronDown } from "lucide-vue-next";

const props = defineProps({
    headline: { type: String, default: null },
    subtitle: { type: String, default: null },
    features: { type: Array, default: null },
    wide: { type: Boolean, default: false },
});

const { locale } = useI18n();
const route = useRoute();
const router = useRouter();
const showLangMenu = ref(false);
const currentLanguage = ref("Français");

const isNl = computed(() => locale.value === "nl");

const displayHeadline = computed(
    () => props.headline ?? (isNl.value ? "Zetelverplaatsing, zonder gedoe." : "Le transfert de siège, sans friction.")
);
const displaySubtitle = computed(
    () =>
        props.subtitle ??
        (isNl.value
            ? "KBO-opzoeking, identiteitsverificatie en aktegeneratie — een begeleid, snel en conform traject."
            : "Recherche BCE, vérification d'identité et génération des actes — un parcours guidé, rapide et conforme.")
);
const displayFeatures = computed(
    () =>
        props.features ??
        (isNl.value
            ? ["Realtime ondernemingsgegevens (KBO)", "Veilige identiteitsverificatie (Veriff)", "Formulieren & notulen klaar om in te dienen"]
            : ["Données entreprise en temps réel (BCE)", "Vérification d'identité sécurisée (Veriff)", "Formulaires & PV prêts à déposer"])
);

onMounted(() => {
    const fromParam = route.params.lang;
    const seg = window.location.pathname.split("/").pop();
    const lang = fromParam === "fr" || fromParam === "nl" ? fromParam : seg === "fr" || seg === "nl" ? seg : locale.value;
    if (lang === "fr" || lang === "nl") {
        locale.value = lang;
        currentLanguage.value = lang === "fr" ? "Français" : "Nederlands";
    }
});

const changeLanguage = (lang) => {
    locale.value = lang;
    currentLanguage.value = lang === "fr" ? "Français" : "Nederlands";
    showLangMenu.value = false;
    if (route.params.lang !== undefined) {
        router.replace({ name: route.name, params: { ...route.params, lang }, query: route.query }).catch(() => {});
    }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
