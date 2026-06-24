<script setup>
import { ref, onMounted, watch } from "vue";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";

const { t } = useI18n();


const demande_id = ref(sessionStorage.getItem("demande_id"));
const loading = ref(false);
const emit = defineEmits(['enableNextStep']);

const details = ref({
    nom: "",
    numero: "",
    address: "",
});

const fetchCompanyDetails = async (demande_id) => {
    if (!demande_id) return console.log("No demande id");

    loading.value = true;
    try {
        const { data } = await axiosInstance.get(`demandes/${demande_id}`);
        const bce_data = JSON.parse(data.demande.bce_data);
        console.log(bce_data);
        const enterpriseNumber = data.demande.enterprise_number || bce_data.number;
        if (enterpriseNumber) {
            details.value.nom = bce_data?.denomination?.description?.value ?? bce_data?.denomination?.[0]?.description?.value;
            details.value.numero = enterpriseNumber;
            details.value.address =
                `${bce_data?.address?.street?.description?.value ?? ""}, ` +
                `${bce_data?.address?.houseNumber ?? ""} ${bce_data?.address?.box ?? ""} ` +
                `${bce_data?.address?.zipcode ?? ""} ${bce_data?.address?.municipality?.description?.value ?? ""} ` +
                `${bce_data?.address?.country ?? ""}`;
        }
    } catch (error) {
        console.log(error);
    } finally {
        loading.value = false;
        emit('enableNextStep');
    }
};

onMounted(()=>{
    fetchCompanyDetails(demande_id.value);});

// watch(demande_id,()=>{
//     fetchCompanyDetails();
// })

</script>
<template>
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[120px]">
        <svg class="animate-spin h-7 w-7 text-[#059669] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span>{{ $t('step1Validate.loadingText') }}</span>
    </div>
    <div v-else class="result-card" id="result-card">
        <h3>{{ $t('step1Validate.companyInfo') }}</h3>

        <div class="result-info">
            <div class="result-label">{{ $t('step1Validate.companyName') }}</div>
            <div class="result-value" id="company-name">{{ details.nom }}</div>
        </div>

        <div class="result-info">
            <div class="result-label">{{ $t('step1Validate.companyNumber') }}</div>
            <div class="result-value" id="siret-number">
                {{ details.numero }}
            </div>
        </div>

        <div class="result-info">
            <div class="result-label">{{ $t('step1Validate.address') }}</div>
            <div class="result-value" id="company-address">
                {{ details.address }}
            </div>
        </div>
    </div>
</template>
<style scoped>
:root {
    --primary: #059669;
    --primary-dark: #047857;
    --secondary: #0f766e;
    --light: #f8f9fa;
    --dark: #212529;
    --success: #4cc9f0;
    --error: #e63946;
    --gradient: linear-gradient(135deg, #059669, #10b981);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f6f8fc;
    color: var(--dark);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 500px;
}

.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-header {
    background: var(--gradient);
    color: white;
    padding: 25px 30px;
    position: relative;
}

.card-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.card-header p {
    font-size: 14px;
    opacity: 0.9;
}

.card-body {
    padding: 30px;
}

.input-group {
    margin-bottom: 25px;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.input-field {
    position: relative;
}

.input-field input {
    width: 100%;
    padding: 15px 20px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.input-field input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
}

.btn {
    background: var(--gradient);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
}

.btn:active {
    transform: translateY(0);
}

.result-card {
    margin-top: 25px;
    padding: 20px 25px;
    border-radius: 10px;
    background-color: #f0f7ff;
    border-left: 4px solid var(--primary);
    /* display: none; */
}

.result-card.visible {
    display: block;
    animation: fadeIn 0.5s ease;
}

.result-card h3 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--primary-dark);
}

.result-info {
    margin-bottom: 10px;
}

.result-label {
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
}

.result-value {
    font-size: 16px;
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.error-message {
    color: var(--error);
    padding: 10px;
    margin-top: 20px;
    border-radius: 6px;
    background-color: rgba(230, 57, 70, 0.1);
    display: none;
}

.loader {
    display: none;
    text-align: center;
    margin: 20px 0;
}

.loader span {
    display: inline-block;
    width: 10px;
    height: 10px;
    margin: 0 3px;
    border-radius: 50%;
    background: var(--primary);
    animation: loader 1s infinite ease-in-out;
}

.loader span:nth-child(2) {
    animation-delay: 0.1s;
}

.loader span:nth-child(3) {
    animation-delay: 0.2s;
}

@keyframes loader {
    0%,
    100% {
        transform: scale(0.3);
        opacity: 0.5;
    }
    50% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 576px) {
    .card-header {
        padding: 20px;
    }

    .card-body {
        padding: 20px;
    }

    .input-field input {
        padding: 12px 15px;
    }

    .btn {
        padding: 12px 20px;
    }
}
</style>
