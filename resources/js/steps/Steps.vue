<template>
    <div class="aurora-wrap relative min-h-screen w-full bg-slate-50">
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:440px;height:440px;background:#a7f3d0;top:-180px;right:-90px;opacity:.22;"></div>
            <div class="aurora-blob" style="width:380px;height:380px;background:#6ee7b7;bottom:-200px;left:-130px;opacity:.16;"></div>
        </div>

        <!-- top bar -->
        <header class="relative z-20 mx-auto flex max-w-5xl items-center justify-between px-5 py-5 sm:px-8">
            <router-link :to="{ name: isAdmin ? 'admin' : 'client' }" class="flex items-center gap-2.5">
                <div class="brand-gradient grid h-9 w-9 place-items-center rounded-xl">
                    <FileCheck2 class="h-5 w-5 text-white" />
                </div>
                <span class="font-display text-lg font-extrabold tracking-tight text-slate-900">Aktly<span class="text-emerald-600"> Lite</span></span>
            </router-link>
            <router-link
                :to="{ name: isAdmin ? 'admin' : 'client' }"
                class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white/70 px-3.5 py-2 text-sm font-medium text-slate-600 backdrop-blur transition hover:border-emerald-300 hover:text-slate-800"
            >
                <X class="h-4 w-4" />
                <span class="hidden sm:inline">{{ isNl ? 'Sluiten' : 'Quitter' }}</span>
            </router-link>
        </header>

        <!-- stepper -->
        <div class="relative z-10 mx-auto max-w-xl px-8 pb-10 pt-2">
            <div class="relative flex justify-between">
                <!-- track (centered on the circles) -->
                <div class="absolute left-5 right-5 top-5 h-0.5 -translate-y-1/2 rounded-full bg-slate-200"></div>
                <!-- progress -->
                <div class="absolute left-5 top-5 h-0.5 -translate-y-1/2 rounded-full bg-emerald-500 transition-all duration-500" :style="progressStyle"></div>

                <!-- steps -->
                <div v-for="(s, i) in steps" :key="s.key" class="relative z-10">
                    <div
                        class="grid h-10 w-10 place-items-center rounded-2xl border-2 transition-all duration-300"
                        :class="i < currentIndex
                            ? 'border-emerald-500 bg-emerald-500 text-white'
                            : i === currentIndex
                            ? 'border-emerald-500 bg-white text-emerald-600 ring-4 ring-emerald-500/15'
                            : 'border-slate-200 bg-white text-slate-300'"
                    >
                        <Check v-if="i < currentIndex" class="h-5 w-5" />
                        <component v-else :is="s.icon" class="h-[18px] w-[18px]" />
                    </div>
                    <span
                        class="absolute left-1/2 top-[calc(100%+0.5rem)] w-24 -translate-x-1/2 text-center text-[11px] font-semibold leading-tight sm:text-xs"
                        :class="i <= currentIndex ? 'text-slate-800' : 'text-slate-400'"
                    >{{ s.label }}</span>
                </div>
            </div>
        </div>

        <!-- content -->
        <main class="relative z-10 mx-auto w-full max-w-3xl px-4 pb-20 pt-8 sm:px-6">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { computed, ref, provide, watch, onMounted } from "vue";
import { useAuthStore } from "@/pinia.js";
import { useI18n } from "vue-i18n";
import { useRoute } from "vue-router";
import { FileCheck2, Check, X, Building2, UserCheck, MapPin, FileText } from "lucide-vue-next";

const auth = useAuthStore();
const route = useRoute();
const { locale } = useI18n();

const isAdmin = computed(() => auth.isAdmin);
const isNl = computed(() => locale.value === "nl");

const steps = computed(() => [
    { key: "entreprise", label: isNl.value ? "Onderneming" : "Entreprise", icon: Building2, names: ["step1"] },
    { key: "depositaire", label: isNl.value ? "Indiener" : "Dépositaire", icon: UserCheck, names: ["step2"] },
    { key: "adresse", label: isNl.value ? "Adres" : "Adresse", icon: MapPin, names: ["addressService"] },
    { key: "documents", label: isNl.value ? "Documenten" : "Documents", icon: FileText, names: ["form"] },
]);

const currentIndex = computed(() => {
    const idx = steps.value.findIndex((s) => s.names.includes(route.name));
    return idx < 0 ? 0 : idx;
});

// Progress line width: from the first circle's center to the current step's center.
// left-5/right-5 inset the track by one circle-radius (1.25rem) on each side, so the
// usable span is (100% - 2.5rem); the fraction is currentIndex / (steps - 1).
const progressStyle = computed(() => {
    const denom = Math.max(1, steps.value.length - 1);
    const frac = Math.min(currentIndex.value, denom) / denom;
    return { width: `calc((100% - 2.5rem) * ${frac})` };
});

// Keep providing updateStep so existing step components that inject it keep working.
const demandeId = ref(sessionStorage.getItem("demande_id") || "");
const activeStep = ref(Number(sessionStorage.getItem("activeStep")) || 1);
watch(activeStep, (v) => sessionStorage.setItem("activeStep", v));
const updateStep = (step) => {
    activeStep.value = step;
};
provide("updateStep", updateStep);

watch(
    () => route.fullPath,
    () => {
        demandeId.value = sessionStorage.getItem("demande_id") || "";
    }
);

onMounted(async () => {
    if (!auth.user) {
        try {
            await auth.fetchUser?.();
        } catch (_) {}
    }
});
</script>
