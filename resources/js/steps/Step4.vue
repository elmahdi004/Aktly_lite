<template>
    <div class="min-h-screen w-full p-6">
        <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded text-center font-semibold">
            <span>{{ $t('cessionParts.avertissement') }}
                <br>
                {{ $t('step4.txtavertissement') }}
            </span>
        </div>
        <div class="titles text-center">
            <h1 class="text-2xl font-bold">{{ t('generateTextIa.title') }}</h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ t('generateTextIa.subtitle') }}
        </p>
        <form method="POST" @submit.prevent="saveTextPublication()">
            <div class="mt-8 flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="text" class="text-sm font-semibold text-[#64748b]">
                        {{ t('generateTextIa.label') }}
                    </label>
                    <textarea id="textGenere" :disabled="loading" v-model="textGenere" rows="10"
                        class="w-full p-4 border border-[#e0e0e0] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3f51b5]"></textarea>
                </div>
                <center><div v-if="loading" class="mt-4 text-yellow-600 ">{{ $t("step4.loading") }}</div></center>
                <center><div v-if="submitError" class="mt-4 text-red-600">{{ submitError }}</div></center>
                
                <div class="buttons flex mt-6 justify-center">
                    <button
                        @click="prevStep()"
                        type="button"
                        class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]"
                    >
                        {{ $t('step1Validate.previousButton') }}
                    </button>
                    <button :disabled="loading" type="button" @click="saveTextPublication()"
                        class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50">
                        {{ loading ? $t('generateTextIa.loading') : $t('step3.continue') }}
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
const router = useRouter();
const route = useRoute();
const autoContinue = ref(route.query.auto_continue === "1");

const updateStep = inject("updateStep");
const demande_id = sessionStorage.getItem("demande_id") || 0;

const storageKey = "ia_text_pv";

const textGenere = ref("");
const submitError = ref("");

onMounted( async () => {
    const routeDemandeId = route.query?.demande_id ? String(route.query.demande_id) : null;
    if (routeDemandeId && !sessionStorage.getItem("demande_id")) {
        sessionStorage.setItem("demande_id", routeDemandeId);
    }

    window.addEventListener("ia-text-updated", handleSidebarUpdate);
    checkCreditsAndRedirect();
    if (updateStep) updateStep(7);
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
            saveTextPublication();
        }
    }
});
onBeforeUnmount(() => {
    window.removeEventListener("ia-text-updated", handleSidebarUpdate);
});
const prevStep = async () => {
    const demande = await getDemandeById();
        if (demande == null) {
            return;
        }
        const address_service = demande.demande.address_service;
        const dirigeants_service = demande.demande.dirigeants_service;
        const cession_parts_service = demande.demande.cession_parts_service;
        if (cession_parts_service == 1) {
            router.push({ name: "cessionService" });
        } else if (dirigeants_service == 1) {
            router.push({ name: "dirigeantsService" });
        } else if (address_service == 1) {
            router.push({ name: "addressService" });
        }
};
const getDemandeById = async () => {
    // loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    try {
        const response = await axiosInstance.get(`demandes/${demande_id}`);
        return response.data;
    } catch (error) {
        console.error("Error fetching address:", error);
        return null;
    } finally {
        // loading.value = false;
    }
};
const sanitizePvTextClient = (text) => {
    if (!text) return "";
    let cleaned = String(text);
    cleaned = cleaned.replace(/^.*\bE\s*\.?\s*L\s*\.?\s*P\s*\.?\b.*(?:\r?\n|$)/gim, "");
    const match = cleaned.match(/(proc(?:e|\u00e8)s[\s\-\u2010\u2011\u2012\u2013\u2014]*verbal|proces[\s\-\u2010\u2011\u2012\u2013\u2014]*verbaal)/i);
    if (match && match.index != null) {
        cleaned = cleaned.slice(match.index).trimStart();
    }
    return cleaned.trim();
};

const getTextWithIA = async () => {
    try {
        const response = await axiosInstance.get(`/getTextWithIA/${demande_id}`);
        if (response && response.data) {
            // modifier value textearea qui a id textGenere par response.data
            textGenere.value = sanitizePvTextClient(response.data.response);
            sessionStorage.setItem(storageKey, textGenere.value || "");
        }
        sessionStorage.removeItem("missing_fields_data");
    } catch (error) {
        if (error?.response?.status === 422 && error?.response?.data?.missing_fields) {
            redirectToMissingFields(error.response.data.missing_fields || []);
            return;
        }
        console.error('Failed to load text from IA:', error);
    }

};


const redirectToMissingFields = (fields) => {
    sessionStorage.setItem(
        "missing_fields_data",
        JSON.stringify({ source: "step4", fields: fields || [] })
    );
    router.push({ name: "step6" });
};
const handleSidebarUpdate = (event) => {
    if (event?.detail?.type === "pv" && event?.detail?.text) {
        textGenere.value = sanitizePvTextClient(event.detail.text);
    }
};

watch(
    textGenere,
    (value) => {
        sessionStorage.setItem(storageKey, value || "");
    },
    { immediate: true }
);
const saveTextPublication = async () => {
    try {
        submitError.value = "";
        loading.value = true;
        const demande_id = sessionStorage.getItem("demande_id");
        if (!demande_id) {
            submitError.value = "Demande introuvable. Veuillez recharger la page.";
            return;
        }
        if (demande_id) {
            try {
                await axiosInstance.post(`/demandes/${demande_id}/progress`, { progress: 100 });
            } catch (e) {
                console.error("Failed to update progress", e);
            }
        }
        const cleaned = sanitizePvTextClient(textGenere.value);
        textGenere.value = cleaned;
        await axiosInstance.post(`/demandes/${demande_id}/text`, {
            text: cleaned,
        });

        router.push({ name: "step5" });
    } catch (error) {
        if (error?.response?.status === 422 && error?.response?.data?.missing_fields) {
            redirectToMissingFields(error.response.data.missing_fields || []);
            return;
        }
        console.error(error);
        const status = error?.response?.status;
        const apiMessage = error?.response?.data?.message || error?.response?.data?.error;
        submitError.value =
            apiMessage ||
            (status ? `Erreur ${status}. Veuillez reessayer.` : t("step2.errorServer") || "Une erreur est survenue.");
    } finally {
        loading.value = false;
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