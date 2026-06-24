<template>
  <div class="p-6">
    <div class="titles text-center mt-4">
      <h1 class="text-2xl font-bold">{{ $t("step3.title") }}</h1>
    </div>
    <p class="text-[#64748b] text-sm mt-4 text-center">{{ $t("step3.description") }}</p>

    <div class="lesChoix flex flex-col gap-y-6 mt-5">
      <div class="flex flex-col gap-y-3 w-full max-w-md">
        <label>{{ $t("step3.changeDate") }}</label>
        <DatePicker v-model="date_changement" class="w-full" />
      </div>
      <div class="flex flex-col gap-y-3 w-full max-w-md">
        <label>{{ $t("step3.generalMeetingDate") }}</label>
        <DateTimePicker v-model="date_assemblee" class="w-full" />
      </div>
      <div class="flex flex-col gap-y-3 w-full max-w-md">
        <label>{{ $t("step3.faitA") }}</label>
        <input
          required
          v-model="fait_a"
          type="text"
          class="border rounded px-3 py-2 w-full"
          :placeholder="$t('step3.cityPlaceholder')"
        />
      </div>
      <p class="text-[#726265] text-sm mt-4 text-center min-h-5">{{ error }}</p>
    </div>

    <div class="buttons flex mt-6 justify-center">
      <button
        :disabled="loading"
        @click="prevStep()"
        type="button"
        class="mx-4 px-10 py-2 border rounded-full text-[#059669] border-[#059669] bg-white hover:bg-[#f2f2f2]"
      >
        {{ $t('step1Validate.previousButton') }}
      </button>
      <button
        :disabled="loading"
        @click="update_dates_fn()"
        type="button"
        class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669] disabled:bg-[#059669]/50"
      >
        {{ loading ? $t("step3.loading") : $t("step3.continue") }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import DateTimePicker from "@/components/ui/DateTimePicker.vue";
import axiosInstance from "@/axios";
import { useRouter } from "vue-router";
import { CalendarDateTime } from "@internationalized/date";
import {CalendarDate} from "@internationalized/date";
import DatePicker from "@/components/ui/DatePicker.vue";

const { t } = useI18n();

const date_changement = ref(null);
const date_assemblee = ref(null);
const loading = ref(false);
const error = ref("");
const fait_a = ref("");

const updateStep = inject("updateStep");
const router = useRouter();

onMounted(async () => {
  await checkCreditsAndRedirect();
  if (updateStep) updateStep(5);
  getDemande();
});

const getDemande = async () => {
  const demande_id = sessionStorage.getItem("demande_id");
  if (demande_id) {
    await axiosInstance
      .get(`demandes/${demande_id}`)
      .then(({ data }) => {
        fait_a.value = data.demande.fait_a;
        if (data.demande.date_changement) {
          const [year, month, day] = data.demande.date_changement.split("-").map(Number);
                    date_changement.value = new CalendarDate(year, month, day);
        }
        if (data.demande.date_assemblee) {
          const parts = data.demande.date_assemblee.split(/[-T:\s]/).map(Number);
          const [year, month, day, hours = 0, minutes = 0] = parts;
          date_assemblee.value = new CalendarDateTime(year, month, day, hours, minutes);
        }
      })
      .catch((error) => console.log(error));
  }
};

const prevStep = () => {
  router.push({ name: "servicesStep" });
};

const update_dates_fn = async () => {
  error.value = "";
  loading.value = true;
  const demande_id = sessionStorage.getItem("demande_id");
  if (!date_assemblee.value || !date_changement.value || !fait_a.value) {
    error.value = t("step3.errorMissingDates");
    loading.value = false;
    return;
  }
  try {
    await axiosInstance.put(`/demandes/${demande_id}`, {
      date_changement:
                date_changement.value["year"] +
                "-" +
                date_changement.value["month"] +
                "-" +
                date_changement.value["day"],
      date_assemblee: date_assemblee.value
        ? `${date_assemblee.value.year}-${String(date_assemblee.value.month).padStart(2, "0")}-${String(date_assemblee.value.day).padStart(2, "0")} ${String(date_assemblee.value.hour ?? 0).padStart(2, "0")}:${String(date_assemblee.value.minute ?? 0).padStart(2, "0")}`
        : null,
      fait_a: fait_a.value,
    });
    if (demande_id) {
      try {
        await axiosInstance.post(`/demandes/${demande_id}/progress`, { progress: 75 });
      } catch (e) {
        console.error("Failed to update progress", e);
      }
    }
    get_the_active_demande();
  } catch (error) {
    console.log(error);
  } finally {
    loading.value = false;
  }
};

const get_the_active_demande = async () => {
  const demande_id = sessionStorage.getItem("demande_id");
  await axiosInstance
    .get(`demandes/${demande_id}`)
    .then(({ data }) => {
      const { address_service, dirigeants_service, cession_parts_service } = data.demande;
      if (address_service == 1) router.push({ name: "addressService" });
      else if (dirigeants_service == 1) router.push({ name: "dirigeantsService" });
      else if (cession_parts_service == 1) router.push({ name: "cessionService" });
    })
    .catch((error) => console.log(error));
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
    error.value = t("step1.errorCheckingCredits");
    return false;
  }
};
</script>
