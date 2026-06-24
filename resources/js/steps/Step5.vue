<template>
    <div class="min-h-screen w-full p-6">
        <div  class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded text-center font-semibold">
            <span>{{ $t('cessionParts.avertissement') }}
                <br>
                {{ $t('step4.txtavertissement') }}
            </span>
        </div>
        <div class="titles text-center">
            <h1 class="text-2xl font-bold">{{ t('generateTextIa.titleextrait') }}</h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ t('generateTextIa.subtitleextrait') }}
        </p>
        <form method="POST" @submit.prevent="saveTextPublication()">
            <div class="mt-8 flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="text" class="text-sm font-semibold text-[#64748b]">
                        {{ t('generateTextIa.labelextrait') }}
                    </label>
                    <textarea id="textGenere" :disabled="loading" v-model="textGenere" rows="10"
                        class="w-full p-4 border border-[#e0e0e0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3f51b5]"></textarea>
                </div>
                <center>
                    <div v-if="loading" class="mt-4 text-yellow-600">
                        {{ $t("step4.loading") }}
                        <div class="mt-2 w-full max-w-md mx-auto">
                            <div class="h-2 rounded-full bg-[#e0e0e0] overflow-hidden">
                                <div
                                    class="h-2 bg-[#059669] transition-[width] duration-300"
                                    :style="{ width: `${loadingProgress}%` }"
                                ></div>
                            </div>
                            <div class="mt-1 text-xs text-[#64748b]">{{ loadingProgress }}%</div>
                        </div>
                    </div>
                </center>
                <div class="buttons flex mt-6 justify-center">
                    <button
                        @click="prevStep()"
                        type="button"
                        class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]"
                    >
                        {{ $t('step1Validate.previousButton') }}
                    </button>
                    <button :disabled="loading" @click="continueHandle()" type="submit"
                        class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50">
                        {{ loading ? `${$t('generateTextIa.loading')} ${loadingProgress}%` : $t('generateTextIa.terminer') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>



<script setup>
import axiosInstance from "@/axios.js";
import { ref, onMounted, inject, watch, onBeforeUnmount } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const loading = ref(false);
const loadingProgress = ref(0);
const loadingTimer = ref(null);
const router = useRouter();
const route = useRoute();
const autoContinue = ref(route.query.auto_continue === "1");

const updateStep = inject("updateStep");
const demande_id = sessionStorage.getItem("demande_id") || 0;

const storageKey = "ia_text_extrait";

const textGenere = ref("");

onMounted( async () => {
    const routeDemandeId = route.query?.demande_id ? String(route.query.demande_id) : null;
    if (routeDemandeId && !sessionStorage.getItem("demande_id")) {
        sessionStorage.setItem("demande_id", routeDemandeId);
    }

    window.addEventListener("ia-text-updated", handleSidebarUpdate);
    checkCreditsAndRedirect();
    if (updateStep) updateStep(8);
    loading.value = true;
    try {
        await getTextWithIA();
    } catch (e) {
        console.error('Error in getTextWithIA:', e);
    } finally {
        loading.value = false;
        if (autoContinue.value) {
            autoContinue.value = false;
            router.replace({ name: route.name, query: {} });
            if (!loading.value) {
                continueHandle();
            }
        }
    }
});
onBeforeUnmount(() => {
    window.removeEventListener("ia-text-updated", handleSidebarUpdate);
    stopLoadingProgress();
});
const prevStep = async () => {
    router.push({ name: "step4" });
};
const getTextWithIA = async () => {
    try {
        const response = await axiosInstance.get(`/getExtraitTextWithIA/${demande_id}`);
        console.log('Response from getExtraitTextWithIA:', response.data);
        if (response?.data?.response) {
            textGenere.value = response.data.response;
            sessionStorage.setItem(storageKey, textGenere.value || "");
        }
        sessionStorage.removeItem("missing_fields_data");
    } catch (error) {
        if (error?.response?.status === 422 && error?.response?.data?.missing_fields) {
            redirectToMissingFields(error.response.data.missing_fields || []);
            return;
        }
        console.error('Failed to load extrait from IA:', error);
        throw error;
    }
};

const redirectToMissingFields = (fields) => {
    sessionStorage.setItem(
        "missing_fields_data",
        JSON.stringify({ source: "step5", fields: fields || [] })
    );
    router.push({ name: "step6" });
};
const handleSidebarUpdate = (event) => {
    if (event?.detail?.type === "extrait" && event?.detail?.text) {
        textGenere.value = event.detail.text;
    }
};

const startLoadingProgress = () => {
    stopLoadingProgress();
    loadingProgress.value = Math.max(loadingProgress.value, 5);
    loadingTimer.value = setInterval(() => {
        if (!loading.value) return;
        if (loadingProgress.value < 95) {
            const increment = Math.floor(Math.random() * 4) + 2; // 2-5
            loadingProgress.value = Math.min(95, loadingProgress.value + increment);
        }
    }, 300);
};

const stopLoadingProgress = () => {
    if (loadingTimer.value) {
        clearInterval(loadingTimer.value);
        loadingTimer.value = null;
    }
};

const finishLoadingProgress = () => {
    stopLoadingProgress();
    loadingProgress.value = 100;
    setTimeout(() => {
        if (!loading.value) {
            loadingProgress.value = 0;
        }
    }, 800);
};

watch(
    textGenere,
    (value) => {
        sessionStorage.setItem(storageKey, value || "");
    },
    { immediate: true }
);
watch(
    loading,
    (value) => {
        if (value) {
            startLoadingProgress();
        } else {
            finishLoadingProgress();
        }
    },
    { immediate: true }
);
const continueHandle = async () => {
    loading.value = true;
    // Update progress to 100
    const demande_id = sessionStorage.getItem("demande_id");
    if (demande_id) {
        try {
            await axiosInstance.post(`/demandes/${demande_id}/progress`, { progress: 100 });
        } catch (e) {
            console.error("Failed to update progress", e);
        }
    }
    saveTextPublication();
};

const saveTextPublication = async () => {
    try {
        const response = await axiosInstance.post(`/demandes/${demande_id}/extraitText`, {
            text: textGenere.value,
        });
        const pdfMissing = await checkMissingPdfFields();
        if (pdfMissing.length) {
            sessionStorage.setItem(
                "missing_fields_data",
                JSON.stringify({
                    source: "finish",
                    fields: pdfMissing,
                    mode: "pdf",
                    form: "",
                })
            );
            router.push({ name: "step6" });
            loading.value = false;
            return;
        }

        sessionStorage.removeItem("demande_id");
        sessionStorage.removeItem("entreprise_number");
        router.push({ name: "finish" });
        loading.value = false;
    } catch (error) {
        if (error?.response?.status === 422 && error?.response?.data?.missing_fields) {
            redirectToMissingFields(error.response.data.missing_fields || []);
            return;
        }
        console.error(error);
        loading.value = false;
    }
};

const checkMissingPdfFields = async () => {
    const demandeId = sessionStorage.getItem("demande_id");
    if (!demandeId) return [];

    try {
        const [form1, form2] = await Promise.all([
            axiosInstance.get(`/demandes/${demandeId}/missing-pdf-fields`, {
                params: { form: "formulaire1" },
            }),
            axiosInstance.get(`/demandes/${demandeId}/missing-pdf-fields`, {
                params: { form: "formulaire2" },
            }),
        ]);

        const missing = [
            ...(form1?.data?.missing_fields || []),
            ...(form2?.data?.missing_fields || []),
        ];

        const seen = new Set();
        return missing.filter((item) => {
            if (!item?.key) return false;
            if (seen.has(item.key)) return false;
            seen.add(item.key);
            return true;
        });
    } catch (error) {
        console.error("Failed to check missing PDF fields:", error);
        return [];
    }
};
const checkCreditsAndRedirect = async () => {
    try {
        const routeDemandeId = route.query?.demande_id ? String(route.query.demande_id) : null;
        const demande_id = sessionStorage.getItem("demande_id") || routeDemandeId;
        if (demande_id && !sessionStorage.getItem("demande_id")) {
            sessionStorage.setItem("demande_id", demande_id);
        }

        if (demande_id) {
            try {
                const demandeResponse = await axiosInstance.get(`/demandes/${demande_id}`);
                if (demandeResponse.data && demandeResponse.data.demande) {
                    console.log("Resuming existing demande, skipping subscription check");
                    return true;
                }
            } catch (demandeError) {
                console.log("Demande not found or not accessible, checking subscription");
            }
        }

        const { data } = await axiosInstance.get('/billing/subscription');
        const hasValidSubscription = data.active;
        const hasValidCredits = Number(data.credits || 0) > 0;

        if (!hasValidSubscription && !hasValidCredits) {
            router.push({ name: "buyCredits" });
            return false;
        }
        return true;
    } catch (err) {
        console.error("Error checking subscription:", err);
        error.value = t('step1.errorCheckingCredits');
        return false;
    }
};
</script>