<template>
    <div class="w-full p-6">
        <div class="titles text-center mt-4">
            <h1 class="text-2xl font-bold">
                {{ $t('services.title') }}
            </h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ $t('services.description') }}
        </p>
        <div class="services grid grid-cols-12 mt-20 gap-x-10">
            <ServiceCard
                icon="/assets/address_service.png"
                :title="$t('services.address')"
                name="address"
                v-model="services.address"
            />
            <ServiceCard
                icon="/assets/users.png"
                :title="$t('services.dirigeants')"
                name="dirigeants"
                v-model="services.dirigeants"
            />
            <ServiceCard
                icon="/assets/parts.png"
                :title="$t('services.cessionParts')"
                name="cession_parts"
                v-model="services.cession_parts"
            />
        </div>
        <div class="buttons flex mt-10 justify-center">
            <button
                @click="prevStep()"
                type="button"
                class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]"
            >
                {{ $t('step1Validate.previousButton') }}
            </button>
            <button
                @click="services_fn"
                type="button"
                :disabled="loading || !hasSelection"
                class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50 disabled:cursor-not-allowed"
            >
                {{ loading ? $t('services.loading') : $t('services.nextButton') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import ServiceCard from "./components/ServiceCard.vue";
import { computed, onMounted, inject, ref } from "vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const updateStep = inject("updateStep");
const loading = ref(false);
const services = ref({
    address: false,
    dirigeants: false,
    cession_parts: false,
});
const hasSelection = computed(() =>
    services.value.address || services.value.dirigeants || services.value.cession_parts
);
const router = useRouter();

onMounted( async () => {
    await checkCreditsAndRedirect();
    if (updateStep) updateStep(4);
    getDemande();
});
 const prevStep = () => {
    router.push({ name: "step2" });
};
const getDemande = async () => {
    // loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id") || 0;
    try {
        const response = await axiosInstance.get(`demandes/${demande_id}`);
        services.value.address = response.data.demande.address_service;
        services.value.dirigeants = response.data.demande.dirigeants_service;
        services.value.cession_parts = response.data.demande.cession_parts_service;
    } catch (error) {
        console.error("Error fetching address:", error);
        return null;
    } finally {
        // loading.value = false;
    }
};
const services_fn = async () => {
    if (!hasSelection.value) {
        return;
    }
    loading.value = true;
    const demande_id = sessionStorage.getItem("demande_id");
    await axiosInstance
        .put(`/demandes/${demande_id}`, {
            address_service: services.value.address,
            dirigeants_service: services.value.dirigeants,
            cession_parts_service: services.value.cession_parts,
        })
        .then(({ data }) => {
            router.push({ name: "step3" });
        })
        .catch((error) => {
            console.log(error);
        })
        .finally(() => (loading.value = false));
};

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
        return false;
    }
};
</script>

<style></style>