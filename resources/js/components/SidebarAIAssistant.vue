<template>
    <div v-if="showAssistant" class="mt-4 rounded-xl bg-white/80 backdrop-blur border border-white/40 p-4 shadow-sm">
        <div class="text-sm font-semibold text-[#1f2a5a]">
            {{ $t('sidebar.aiAssistant.title') }}
        </div>

        <div v-if="demandeId" class="mt-3">
            <label class="text-xs text-[#6b7280]">
                {{ $t('sidebar.aiAssistant.instructionLabel') }}
            </label>
            <textarea
                v-model="instruction"
                rows="3"
                class="mt-2 w-full rounded-lg border border-[#e5e7eb] px-3 py-2 text-xs text-[#111827] focus:outline-none focus:ring-2 focus:ring-[#059669]"
                :placeholder="$t('sidebar.aiAssistant.instructionPlaceholder')"
            ></textarea>
            <button
                type="button"
                class="mt-2 w-full text-left text-xs px-3 py-2 rounded-lg bg-[#f3f4ff] text-[#059669] border border-[#c7c7f7] hover:bg-[#e9eaff] disabled:opacity-60"
                @click="improveFromSidebar"
                :disabled="improving"
            >
                {{ improving ? $t('sidebar.aiAssistant.improving') : $t('sidebar.aiAssistant.improve') }}
            </button>
            <div v-if="improveError" class="mt-2 text-[11px] text-red-500">
                {{ improveError }}
            </div>
            <div v-else class="mt-2 text-[11px] text-[#9ca3af]">
                {{ $t('sidebar.aiAssistant.improveHint') }}
            </div>
        </div>
        <div v-else class="mt-3 text-[11px] text-[#9ca3af]">
            {{ $t('sidebar.aiAssistant.noDemande') }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import axiosInstance from "@/axios.js";
import { useRoute } from "vue-router";

const props = defineProps({
    demandeId: {
        type: String,
        default: "",
    },
});

const { locale, t } = useI18n();
const route = useRoute();

const instruction = ref("");
const improving = ref(false);
const improveError = ref("");
const showAssistant = computed(() => {
    const name = route?.name;
    return name === "step4" || name === "step5";
});

watch(
    instruction,
    (value) => {
        sessionStorage.setItem("ia_instruction", value || "");
    },
    { immediate: true }
);

const improveFromSidebar = async () => {
    if (improving.value) return;
    improveError.value = "";

    const name = route?.name;
    const storageKey = name === "step4" ? "ia_text_pv" : "ia_text_extrait";
    const type = name === "step4" ? "pv" : "extrait";
    const text = (sessionStorage.getItem(storageKey) || "").trim();

    if (!text) {
        improveError.value = t("sidebar.aiAssistant.noText");
        return;
    }

    improving.value = true;
    try {
        const response = await axiosInstance.post("/openai/improve-text", {
            text,
            language: locale.value,
            instruction: instruction.value,
        });
        const improved = response?.data?.response;
        if (improved) {
            sessionStorage.setItem(storageKey, improved);
            window.dispatchEvent(
                new CustomEvent("ia-text-updated", {
                    detail: { type, text: improved },
                })
            );
        }
    } catch (error) {
        console.error("Erreur lors de l'amélioration IA :", error);
        improveError.value = t("sidebar.aiAssistant.improveFailed");
    } finally {
        improving.value = false;
    }
};

</script>
