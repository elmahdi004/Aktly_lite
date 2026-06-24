<template>
    <div class="animate-fade-up flex min-h-[60vh] items-center justify-center px-2 py-6">
        <div class="glass-card w-full max-w-lg p-8 text-center sm:p-12">
            <!-- success icon in a mint halo -->
            <div class="animate-fade-up-1 mx-auto mb-6 grid h-24 w-24 place-items-center rounded-full bg-emerald-50 ring-8 ring-emerald-500/10">
                <div class="brand-gradient grid h-16 w-16 place-items-center rounded-full shadow-lg shadow-emerald-500/30">
                    <CheckCircle2 class="h-9 w-9 text-white" stroke-width="2.2" />
                </div>
            </div>

            <h1 class="animate-fade-up-1 font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                {{ $t('finishStep.congratulations') }}
            </h1>

            <div class="animate-fade-up-2 mx-auto mt-4 max-w-md space-y-2 text-sm leading-relaxed text-slate-500">
                <p>{{ $t('finishStep.successMessage1') }}</p>
                <p class="inline-flex items-center justify-center gap-1.5">
                    <Clock class="h-3.5 w-3.5 shrink-0 text-amber-500" />
                    <span>{{ $t('finishStep.successMessage2') }}</span>
                </p>
            </div>

            <div class="animate-fade-up-3 mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                <router-link :to="{ name: 'client' }" class="btn-aurora w-full sm:w-auto">
                    <LayoutDashboard class="h-4 w-4" />
                    <span>{{ $t('finishStep.returnHome') }}</span>
                </router-link>
                <router-link
                    :to="{ name: 'client' }"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800 sm:w-auto"
                >
                    <ArrowRight class="h-4 w-4" />
                    <span>{{ $t('finishStep.returnHome') }}</span>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from "vue-i18n";
import { onMounted } from "vue";
import confetti from "canvas-confetti";
import { CheckCircle2, Clock, LayoutDashboard, ArrowRight } from "lucide-vue-next";

const { t } = useI18n(); // Déclarer t pour les traductions

onMounted(() => {
    const fire = (particleRatio, opts) =>
        confetti({
            origin: { y: 0.7 },
            colors: ["#059669", "#10b981", "#6ee7b7", "#a7f3d0"],
            disableForReducedMotion: true,
            particleCount: Math.floor(200 * particleRatio),
            ...opts,
        });
    fire(0.25, { spread: 26, startVelocity: 55 });
    fire(0.2, { spread: 60 });
    fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
    fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
    fire(0.1, { spread: 120, startVelocity: 45 });
});
</script>
