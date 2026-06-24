<template>
    <div class="w-full max-w-4xl mx-auto px-4 py-6">
        <div class="bg-white/90 backdrop-blur rounded-2xl shadow border border-gray-200/50 p-6">
            <h1 class="text-2xl font-bold text-[#059669]">
                {{ $t('dashboard.modify_steps_title') || 'Modifier la demande' }}
            </h1>
            <p class="text-sm text-gray-600 mt-2">
                {{ $t('dashboard.modify_steps_subtitle') || 'Voici les étapes déjà effectuées. Cliquez sur une étape pour la modifier si nécessaire.' }}
            </p>

            <div v-if="loading" class="mt-6 text-sm text-gray-500">
                {{ $t('dashboard.loading') || 'Chargement...' }}
            </div>

            <div v-else-if="!demandeId" class="mt-6 text-sm text-red-600">
                {{ $t('dashboard.modify_missing_id') || 'ID de demande manquant.' }}
            </div>

            <div v-else class="mt-6 space-y-4">
                <div
                    v-for="(step, index) in steps"
                    :key="step.name"
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl border border-gray-200/60 bg-white"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold',
                                step.completed ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                            ]"
                        >
                            {{ index + 1 }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">{{ step.label }}</div>
                            <div class="text-xs text-gray-500">
                                {{ step.completed ? ( $t('dashboard.step_done') || 'Effectué' ) : ( $t('dashboard.step_pending') || 'À compléter' ) }}
                            </div>
                        </div>
                    </div>
                    <button
                        class="px-4 py-2 rounded-lg bg-[#059669] text-white text-sm font-semibold hover:bg-[#3a36b6]"
                        @click="goToStep(step.routeName)"
                    >
                        {{ $t('dashboard.modify') || 'Modifier' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axiosInstance from '@/axios.js';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const loading = ref(true);
const demande = ref(null);
const demandeId = ref(null);

const normalizeDemandeId = (value) => {
    if (value === undefined || value === null) return null;
    const raw = String(value).trim();
    if (raw === '') return null;
    // Be permissive: accept any non-empty string as demande id
    return raw;
};

const extractFromSearch = (search) => {
    if (!search) return null;
    const normalized = search.startsWith('?') ? search : `?${search}`;
    const params = new URLSearchParams(normalized);
    return normalizeDemandeId(
        params.get('demande_id') ||
        params.get('demande-id') ||
        params.get('demandeId') ||
        null
    );
};

const resolveDemandeId = () => {
    const fromRoute = normalizeDemandeId(
        route.query.demande_id ||
        route.query['demande-id'] ||
        route.query.demandeId ||
        route.params.id ||
        null
    );

    let id = fromRoute;

    if (!id) {
        id = normalizeDemandeId(sessionStorage.getItem('demande_id') || null);
    }

    if (!id) {
        try {
            id = extractFromSearch(window.location.search);
        } catch {
            id = null;
        }
    }

    if (!id) {
        try {
            const hash = window.location.hash || '';
            const hashQueryIndex = hash.indexOf('?');
            if (hashQueryIndex !== -1) {
                id = extractFromSearch(hash.slice(hashQueryIndex + 1));
            }
        } catch {
            id = null;
        }
    }

    if (!id) {
        try {
            const fullPath = route.fullPath || '';
            const url = new URL(fullPath, window.location.origin);
            id = extractFromSearch(url.search);
        } catch {
            id = null;
        }
    }

    demandeId.value = id;

    if (id) {
        sessionStorage.setItem('demande_id', id);
    }

    console.log('ModifyDemande resolveDemandeId', {
        routeQuery: route.query,
        routeParams: route.params,
        search: window.location.search,
        resolvedId: id,
    });
};

const progress = computed(() => {
    return demande.value?.progress ?? 0;
});

const steps = computed(() => [
    { label: t('step1Validate.title'), routeName: 'step1Validate', completed: progress.value >= 25 },
    { label: t('step2.title'), routeName: 'step2', completed: progress.value >= 50 },
    { label: t('services.title'), routeName: 'servicesStep', completed: progress.value >= 75 },
    { label: t('step3.title'), routeName: 'step3', completed: progress.value >= 90 },
    { label: t('dashboard.step_finalize') || 'Finalisation', routeName: 'step4', completed: progress.value >= 100 },
]);

const goToStep = (routeName) => {
    if (!demandeId.value) return;
    sessionStorage.setItem('demande_id', demandeId.value);
    router.push({ name: routeName, query: { demande_id: demandeId.value } });
};

const loadDemande = async () => {
    resolveDemandeId();

    if (!demandeId.value) {
        loading.value = false;
        return;
    }
    sessionStorage.setItem('demande_id', demandeId.value);
    try {
        const response = await axiosInstance.get(`demandes/${demandeId.value}`);
        demande.value = response.data.demande;
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadDemande);
</script>
