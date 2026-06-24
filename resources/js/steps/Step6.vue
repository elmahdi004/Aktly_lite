<template>
    <div class="min-h-screen w-full p-6">
        <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded text-center font-semibold">
            <span>{{ $t('cessionParts.avertissement') }}
                <br>
                {{ t('generateTextIa.missingPageSubtitle') }}
            </span>
        </div>
        <div class="titles text-center">
            <h1 class="text-2xl font-bold">{{ t('generateTextIa.missingPageTitle') }}</h1>
        </div>
        <p class="text-[#64748b] text-sm mt-4 text-center">
            {{ t('generateTextIa.missingSubtitle') }}
        </p>

        <div v-if="!displayFields.length" class="mt-6 p-4 rounded-lg border border-[#e5e7eb] text-center text-sm text-[#6b7280]">
            {{ t('generateTextIa.missingEmpty') }}
        </div>

        <div v-else class="mt-4 p-4 rounded-lg border border-[#fde68a] bg-[#fff7ed]">
            <div class="text-sm font-semibold text-[#92400e]">
                {{ t('generateTextIa.missingTitle') }}
            </div>
            <div class="text-xs text-[#92400e] mt-1">
                {{ t('generateTextIa.missingSubtitle') }}
            </div>
            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="field in displayFields" :key="field.key">
                    <div v-if="field.type === 'info'" class="text-xs text-[#9a3412]">
                        <div class="flex items-start justify-between gap-2">
                            <span>{{ getMissingMessage(field) }}</span>
                            <button
                                v-if="field.route"
                                type="button"
                                class="text-[11px] text-[#059669] underline"
                                @click="openMissingStep(field)"
                            >
                                {{ t('generateTextIa.missingOpenStep') }}
                            </button>
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-[#6b7280]">{{ getMissingLabel(field) }}</label>
                            <button
                                type="button"
                                class="text-[10px] px-2 py-0.5 rounded-full border border-[#c7c7f7] text-[#059669]"
                                @click="toggleField(field.key)"
                            >
                                {{ missingDisabled[field.key] ? 'Activer' : 'Désactiver' }}
                            </button>
                            <span
                                v-if="field.is_missing"
                                class="text-[10px] px-2 py-0.5 rounded-full bg-red-100 text-red-700"
                            >
                                Manquant
                            </span>
                        </div>
                        <button
                            v-if="field.route"
                            type="button"
                            class="self-start text-[11px] text-[#059669] underline"
                            @click="openMissingStep(field)"
                        >
                            {{ t('generateTextIa.missingOpenStep') }}
                        </button>
                        <input
                            v-model="missingValues[field.key]"
                            :type="field.type === 'date' ? 'date' : field.type === 'number' ? 'number' : 'text'"
                            :disabled="missingDisabled[field.key]"
                            :class="[
                                'border rounded-md px-2 py-1 text-sm',
                                missingDisabled[field.key]
                                    ? 'border-[#e5e7eb] bg-gray-200 text-gray-500'
                                    : (field.is_missing ? 'border-red-300 bg-red-50' : 'border-[#e5e7eb]')
                            ]"
                        />
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2 items-center">
                <button
                    type="button"
                    class="px-4 py-2 rounded-md border border-[#c7c7f7] text-[#059669]"
                    @click="goBackToGeneration"
                >
                    {{ t('step1Validate.previousButton') }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 rounded-md text-white bg-[#059669] disabled:opacity-60"
                    :disabled="savingMissing"
                    @click="saveMissingFields"
                >
                    {{ savingMissing ? t('generateTextIa.missingSaving') : t('generateTextIa.missingSave') }}
                </button>
            </div>
        </div>

        <div v-if="showMissingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="w-full max-w-md rounded-lg bg-white p-4 shadow-lg">
                <div class="text-sm font-semibold text-[#111827]">
                    {{ t('generateTextIa.missingModalTitle') }}
                </div>
                <div class="mt-2 text-xs text-[#6b7280]">
                    {{ getMissingStepLabel(selectedMissing) }}
                </div>
                <div class="mt-3 text-xs text-[#9a3412]" v-if="selectedMissing?.type === 'info'">
                    {{ getMissingMessage(selectedMissing) }}
                </div>
                <div class="mt-4 flex gap-2 justify-end">
                    <button
                        type="button"
                        class="px-3 py-2 text-xs rounded-md border border-[#e5e7eb]"
                        @click="closeMissingModal"
                    >
                        {{ t('generateTextIa.missingClose') }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-2 text-xs rounded-md bg-[#059669] text-white"
                        @click="goToMissingStep"
                    >
                        {{ t('generateTextIa.missingGoStep') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axiosInstance from "@/axios.js";
import { ref, onMounted, inject, computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";

const { t, locale } = useI18n();
const router = useRouter();
const updateStep = inject("updateStep");
const demandeId = sessionStorage.getItem("demande_id") || 0;

const missingFields = ref([]);
const missingValues = ref({});
const missingDisabled = ref({});
const savingMissing = ref(false);
const missingError = ref("");
const showMissingModal = ref(false);
const selectedMissing = ref(null);
const sourceRoute = ref("step4");
const missingMode = ref("ia");
const missingForm = ref("");
const addressServiceSelected = ref(true);

const hiddenFieldKeys = new Set([
    "pdf_attestation_company_name",
    "pdf_attestation_sigle",
    "pdf_attestation_n2",
    "pdf_attestation_code_postal2",
    "pdf_attestation_identifier",
    "pdf_attestation_rue2",
    "pdf_attestation_boite2",
    "pdf_attestation_localite2",
    "pdf_attestation_date_de_dissolution",
    "pdf_attestation_date_de_cloture",
]);

const isHiddenField = (field) => {
    if (!field?.key) return false;
    return hiddenFieldKeys.has(field.key);
};

const isNonValue = (value) => {
    if (typeof value !== "string") return false;
    return value.trim().toLowerCase() === "non";
};

const isSeatField = (field) => {
    if (!field?.key) return false;
    if (field.key === "transfert_siege") return true;
    if (field.key.startsWith("pdf_form1_address_")) return true;
    if (field.key.startsWith("pdf_form2_address_")) return true;
    return false;
};

const displayFields = computed(() => {
    const filtered = missingFields.value.filter((field) => {
        if (isHiddenField(field)) {
            console.log("🚫 Hidden field:", field?.key);
            return false;
        }
        if (!addressServiceSelected.value && isSeatField(field)) {
            console.log("🚫 Seat field hidden (no address service):", field?.key);
            return false;
        }
        if (field?.type === "info") return true;
        const value = missingValues.value[field?.key];
        if (isNonValue(value)) {
            console.log("🚫 Non-value field:", field?.key, "=", value);
            return false;
        }
        return true;
    });
    console.log("🎯 Display fields count:", filtered.length, "from:", missingFields.value.length);
    return filtered;
});

const initializeMissingValues = () => {
    if (missingFields.value.length) {
        const defaults = {};
        const disabled = {};
        missingFields.value.forEach((field) => {
            if (isHiddenField(field)) {
                return;
            }
            if (field.type !== "info") {
                let nextValue = field.value ?? "";
                if (typeof nextValue === "string" && nextValue.trim().toLowerCase() === "non") {
                    nextValue = "";
                }
                defaults[field.key] = nextValue;
                disabled[field.key] = false;
            }
        });
        missingValues.value = defaults;
        missingDisabled.value = disabled;
    } else {
        missingValues.value = {};
        missingDisabled.value = {};
    }
};

const fillCapitalAmountFromDemande = async () => {
    if (!demandeId) return;
    const capitalKey = "pdf_capital_amount";
    if (!missingFields.value.some((field) => field?.key === capitalKey)) return;
    if (missingValues.value[capitalKey]) return;
    try {
        const response = await axiosInstance.get(`/demandes/${demandeId}`);
        console.log("✅ Backend demande response:", response?.data);
        const demande = response?.data?.demande || {};
        const montant = demande.montant;
        if (typeof demande.address_service !== "undefined") {
            addressServiceSelected.value = Boolean(demande.address_service);
            console.log("📍 Address service selected:", addressServiceSelected.value);
        }
        if (montant !== undefined && montant !== null && String(montant).trim() !== "") {
            missingValues.value[capitalKey] = montant;
            missingDisabled.value[capitalKey] = false;
            console.log("💰 Capital pre-filled:", montant);
        }
    } catch (error) {
        console.error("❌ Failed to preload capital amount:", error);
    }
};

const refreshPdfMissingFields = async () => {
    if (!demandeId) return;
    try {
        if (missingForm.value) {
            console.log("🔄 Fetching PDF fields for form:", missingForm.value);
            const response = await axiosInstance.get(`/demandes/${demandeId}/pdf-fields`, {
                params: { form: missingForm.value },
            });
            console.log("✅ Backend response (single form):", response?.data);
            missingFields.value = response?.data?.fields || [];
            return;
        }

        console.log("🔄 Fetching PDF fields for both forms...");
        const [form1, form2] = await Promise.all([
            axiosInstance.get(`/demandes/${demandeId}/pdf-fields`, {
                params: { form: "formulaire1" },
            }),
            axiosInstance.get(`/demandes/${demandeId}/pdf-fields`, {
                params: { form: "formulaire2" },
            }),
        ]);

        console.log("✅ Backend response (form1):", form1?.data);
        console.log("✅ Backend response (form2):", form2?.data);

        const allFields = [
            ...(form1?.data?.fields || []),
            ...(form2?.data?.fields || []),
        ];
        console.log("📋 All fields combined:", allFields);

        const seen = new Set();
        missingFields.value = allFields.filter((item) => {
            if (!item?.key) return false;
            if (seen.has(item.key)) return false;
            seen.add(item.key);
            return true;
        });
        console.log("📋 After dedup:", missingFields.value);
    } catch (error) {
        console.error("❌ Failed to refresh missing PDF fields:", error);
    } finally {
        initializeMissingValues();
    }
};

onMounted(async () => {
    if (updateStep) updateStep(9);
    const stored = sessionStorage.getItem("missing_fields_data");
    console.log("📦 Step6 - Raw sessionStorage data:", stored);
    if (stored) {
        try {
            const parsed = JSON.parse(stored);
            console.log("📦 Step6 - Parsed missing_fields_data:", parsed);
            missingFields.value = parsed?.fields || [];
            sourceRoute.value = parsed?.source || "step4";
            missingMode.value = parsed?.mode || "ia";
            missingForm.value = parsed?.form || "";
            console.log("📦 Step6 - Mode:", missingMode.value, "Form:", missingForm.value);
        } catch (e) {
            console.error("❌ Step6 - Parse error:", e);
            missingFields.value = [];
        }
    }

    if (missingMode.value === "pdf") {
        await refreshPdfMissingFields();
        await fillCapitalAmountFromDemande();
    } else {
        initializeMissingValues();
        await fillCapitalAmountFromDemande();
    }
});

const getMissingLabel = (field) => {
    return (locale.value === "nl" ? field.label_nl : field.label_fr) || "";
};
const getMissingMessage = (field) => {
    return (locale.value === "nl" ? field.message_nl : field.message_fr) || "";
};
const getMissingStepLabel = (field) => {
    if (!field) return "";
    return (locale.value === "nl" ? field.step_label_nl : field.step_label_fr) || "";
};
const openMissingStep = (field) => {
    if (!field?.route) return;
    selectedMissing.value = field;
    showMissingModal.value = true;
};
const closeMissingModal = () => {
    showMissingModal.value = false;
    selectedMissing.value = null;
};
const goToMissingStep = () => {
    if (!selectedMissing.value?.route) return;
    const routeName = selectedMissing.value.route;
    closeMissingModal();
    router.push({ name: routeName });
};

const saveMissingFields = async () => {
    if (!demandeId) return;
    savingMissing.value = true;
    missingError.value = "";

    const payload = {};
    missingFields.value.forEach((field) => {
        if (isHiddenField(field)) return;
        if (field.type !== "info") {
            if (missingDisabled.value[field.key]) return;
            let value = missingValues.value[field.key];
            if (typeof value === "string" && value.trim().toLowerCase() === "non") {
                value = "";
            }
            if (value !== undefined && value !== "") {
                payload[field.key] = value;
            }
        }
    });

    console.log("📤 Sending to backend - Payload:", payload);
    console.log("📤 Mode:", missingMode.value, "Form:", missingForm.value);

    try {
        if (missingMode.value === "pdf") {
            const response = await axiosInstance.put(`/demandes/${demandeId}/pdf-fields`, {
                fields: payload,
                form: missingForm.value,
            });
            console.log("✅ Backend save response (PDF):", response?.data);
        } else {
            const response = await axiosInstance.put(`/demandes/${demandeId}`, payload);
            console.log("✅ Backend save response (IA):", response?.data);
        }
    } catch (error) {
        console.error("❌ Failed to save missing fields:", error);
    } finally {
        sessionStorage.removeItem("missing_fields_data");
        savingMissing.value = false;
        goForwardAfterSave();
    }
};

const toggleField = (key) => {
    if (!key) return;
    missingDisabled.value[key] = !missingDisabled.value[key];
    if (missingDisabled.value[key]) {
        missingValues.value[key] = "";
    }
};

const goBackToGeneration = () => {
    let target = sourceRoute.value || "step4";
    if (target === "finish") {
        target = "step5";
    }
    if (target === "form") {
        router.push({ name: target, query: { demande_id: demandeId } });
        return;
    }
    router.push({ name: target });
};

const goForwardAfterSave = () => {
    let target = sourceRoute.value || "step4";
    if (missingMode.value === "pdf" || target === "finish") {
        router.push({ name: "finish" });
        return;
    }
    if (target === "form") {
        router.push({ name: target, query: { demande_id: demandeId, auto_continue: 1 } });
        return;
    }
    router.push({ name: target, query: { auto_continue: 1 } });
};
</script>
