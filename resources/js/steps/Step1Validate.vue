<template>
    <div class="w-[60%]">
        <div>
            <div class="titles text-center mt-4">
                <h1 class="text-2xl font-bold">
                    {{ $t('step1Validate.title') }}
                </h1>
            </div>
            <p class="text-[#64748b] text-sm mt-4">
                {{ $t('step1Validate.description') }}
            </p>
            <div>
                <EnterpriseDetails @enableNextStep="enableNextStepHandle"/>
            </div>
        </div>
        <div class="buttons flex mt-6 justify-center">
            <button
                @click="prevStep()"
                :disabled="true"
                type="button"
                class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2] disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ $t('step1Validate.previousButton') }}
            </button>
            <button
                :disabled="loading"
                @click="nextStep()"
                type="button"
                class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50"
            >
                {{ loading ? $t('step1Validate.loading') : $t('step1Validate.nextButton') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { inject, onMounted, ref } from "vue";
import EnterpriseDetails from "@/services/components/EnterpriseDetails.vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import axios from "axios";
import axiosInstance from "@/axios.js";

const router = useRouter();
const loading = ref(true);
const updateStep = inject("updateStep");
const { t } = useI18n();



const nextStep = async () => {
    loading.value = true;
    // Update progress to 25
    const demande_id = sessionStorage.getItem("demande_id");
    if (demande_id) {
        try {
            await axiosInstance.post(`/demandes/${demande_id}/progress`, { progress: 25 });
        } catch (e) {
            console.error("Failed to update progress", e);
        }
    }
    router.push({ name: "step2" });
};
 const prevStep = () => {
    router.push({ name: "step1" });
};

onMounted(async () => {
    await checkCreditsAndRedirect();
    if (updateStep) updateStep(2);
    
});

const checkCreditsAndRedirect = async () => {
    try {
        const demande_id = sessionStorage.getItem("demande_id");
        if (demande_id) {
            try {
                const demandeResponse = await axiosInstance.get(`/demandes/${demande_id}`);
                if (demandeResponse.data && demandeResponse.data.demande) {
                    console.log("Resuming existing demande, skipping subscription check");
                    return true;
                }
            } catch (demandeError) {
                console.log("Demande verification failed, but demande_id exists - allowing continuation");
                return true;
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
const enableNextStepHandle = () => {
    loading.value = false;
}
</script>