<template>
    <div class="aurora-wrap relative min-h-screen w-full bg-slate-50">
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:440px;height:440px;background:#a7f3d0;top:-180px;right:-90px;opacity:.22;"></div>
            <div class="aurora-blob" style="width:380px;height:380px;background:#6ee7b7;bottom:-200px;left:-130px;opacity:.16;"></div>
        </div>

        <div class="relative z-10 mx-auto w-full max-w-3xl px-4 pb-20 pt-10 sm:px-6">
            <!-- ===== PageHeader ===== -->
            <div class="mb-7 flex items-center gap-3.5 animate-fade-up">
                <div class="brand-gradient grid h-12 w-12 shrink-0 place-items-center rounded-2xl">
                    <LifeBuoy class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        {{ $t('form.title') }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $t('form.subtitle') }}</p>
                </div>
            </div>

            <!-- ===== Form card ===== -->
            <div class="glass-card animate-fade-up-1 p-6 sm:p-8">
                <form @submit.prevent="handleSubmit" enctype="multipart/form-data" class="space-y-7">
                    <!-- Type de réclamation : segmented selector -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                            {{ $t('form.type.label') }}
                        </label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <button
                                v-for="type in requestTypes"
                                :key="type.id"
                                type="button"
                                @click="requestForm.type = type.id"
                                class="flex items-start gap-3 rounded-xl border-2 px-4 py-3 text-left transition-all"
                                :class="requestForm.type === type.id
                                    ? 'border-emerald-500 bg-emerald-50/60 ring-4 ring-emerald-500/10'
                                    : 'border-slate-200 hover:border-slate-300'"
                            >
                                <span
                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-xl transition"
                                    :class="requestForm.type === type.id ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400'"
                                >
                                    <component :is="type.icon" class="h-[18px] w-[18px]" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-800">{{ $t(type.label) }}</span>
                                    <span class="mt-0.5 block text-xs leading-snug text-slate-400">{{ $t(type.description) }}</span>
                                </span>
                                <span
                                    class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 transition"
                                    :class="requestForm.type === type.id ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300'"
                                >
                                    <Check v-if="requestForm.type === type.id" class="h-3 w-3 text-white" />
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Demande liée -->
                    <div v-if="demandes && demandes.length > 0">
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            {{ $t('form.demande.label') }}
                        </label>
                        <div class="relative">
                            <Link2 class="pointer-events-none absolute left-3.5 top-1/2 z-10 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                            <select
                                v-model="requestForm.demande_id"
                                class="input-aurora cursor-pointer appearance-none pl-11 pr-10"
                                required
                            >
                                <option value="" disabled>{{ $t('form.demande.placeholder') }}</option>
                                <option v-for="demande in demandes" :key="demande.id" :value="demande.id">
                                    {{ demande.enterprise_number }} - {{ formatDate(demande.created_at) }}
                                </option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            {{ $t('form.description.label') }}
                        </label>
                        <textarea
                            v-model="requestForm.description"
                            rows="5"
                            class="input-aurora resize-y"
                            :placeholder="$t('form.description.placeholder')"
                            required
                        ></textarea>
                        <p class="mt-1.5 text-xs text-slate-400">
                            {{ 500 - requestForm.description.length }} {{ $t('form.description.remaining') }}
                        </p>
                    </div>

                    <!-- Coordonnées -->
                    <div>
                        <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <UserRound class="h-4 w-4 text-emerald-600" />
                            {{ $t('form.contact.title') }}
                        </h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">
                                    {{ $t('form.contact.name') }}
                                </label>
                                <div class="relative">
                                    <User class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                                    <input
                                        type="text"
                                        v-model="requestForm.nom"
                                        class="input-aurora pl-11"
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">
                                    {{ $t('form.contact.email') }}
                                </label>
                                <div class="relative">
                                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                                    <input
                                        type="email"
                                        v-model="requestForm.email"
                                        class="input-aurora pl-11"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pièces jointes : dropzone -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">
                            {{ $t('form.attachments.label') }}
                        </label>
                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            class="hidden"
                            @change="handleFileChange"
                            :accept="acceptedFileTypes"
                        />
                        <button
                            type="button"
                            @click="triggerFileInput"
                            class="flex w-full flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-white/60 px-6 py-8 text-center transition-all hover:border-emerald-300 hover:bg-emerald-50/30"
                        >
                            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <UploadCloud class="h-5 w-5" />
                            </span>
                            <span class="text-sm font-semibold text-slate-700">{{ $t('form.attachments.add') }}</span>
                            <span class="text-xs text-slate-400" v-if="selectedFiles.length">
                                {{ selectedFiles.length }} {{ $t('form.attachments.selected') }}
                            </span>
                        </button>

                        <ul v-if="selectedFiles.length" class="mt-3 space-y-2">
                            <li
                                v-for="(file, index) in selectedFiles"
                                :key="index"
                                class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white px-4 py-2.5 shadow-sm"
                            >
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <Paperclip class="h-4 w-4 shrink-0 text-emerald-600" />
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-700">{{ file.name }}</span>
                                        <span class="block text-xs text-slate-400">{{ humanFileSize(file.size) }}</span>
                                    </span>
                                </span>
                                <button
                                    type="button"
                                    @click="removeFile(index)"
                                    class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                    :aria-label="$t('form.attachments.remove')"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Message de succès : StatusAlert -->
                    <div
                        v-if="submitSuccess"
                        class="flex items-center gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
                    >
                        <CheckCircle2 class="h-4 w-4 shrink-0" />
                        {{ $t('form.success') }}
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="resetForm"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-slate-800"
                        >
                            {{ $t('form.actions.cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="btn-aurora"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <Send v-else class="h-4 w-4" />
                            <span>{{ isSubmitting ? $t('form.actions.submitting') : $t('form.actions.submit') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";
import { LifeBuoy, Check, ChevronDown, Link2, UserRound, User, Mail, UploadCloud, Paperclip, X, CheckCircle2, Send, Receipt, Wrench, BadgeCheck, MessageSquare } from "lucide-vue-next";

const { t } = useI18n();
const demandes = ref([]);
const fileInput = ref(null);
const selectedFiles = ref([]);
const acceptedFileTypes = ".pdf,.jpg,.jpeg,.png,.doc,.docx";
const isSubmitting = ref(false);
const submitSuccess = ref(false);

onMounted(() => {
  getDemandes();
});

// Types de réclamations (clé de traduction)
const requestTypes = [
  { id: "billing", label: "form.type.billing.label", description: "form.type.billing.description", icon: Receipt },
  { id: "technical", label: "form.type.technical.label", description: "form.type.technical.description", icon: Wrench },
  { id: "quality", label: "form.type.quality.label", description: "form.type.quality.description", icon: BadgeCheck },
  { id: "other", label: "form.type.other.label", description: "form.type.other.description", icon: MessageSquare },
];

// Formulaire
const requestForm = reactive({
  demande_id: null,
  type: "",
  description: "",
  nom: "",
  email: "",
});

// Fonctions utilitaires
const getSelectedType = computed(() => {
  return requestTypes.find((type) => type.id === requestForm.type);
});

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toISOString().split("T")[0];
};

const humanFileSize = (size) => {
  if (!size) return "0 B";
  const i = Math.floor(Math.log(size) / Math.log(1024));
  return (size / Math.pow(1024, i)).toFixed(2) + " " + ["B", "KB", "MB", "GB"][i];
};

// Gestion fichiers
const triggerFileInput = () => fileInput.value && fileInput.value.click();
const handleFileChange = (e) => {
  const files = Array.from(e.target.files || []);
  selectedFiles.value.push(...files);
  e.target.value = "";
};
const removeFile = (index) => selectedFiles.value.splice(index, 1);

// Soumission
const handleSubmit = async () => {
  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append("demande_id", requestForm.demande_id || "");
    formData.append("type", requestForm.type);
    formData.append("description", requestForm.description);
    formData.append("nom", requestForm.nom);
    formData.append("email", requestForm.email);
    selectedFiles.value.forEach((file) => formData.append("attachments[]", file));

    await axiosInstance.post("tickets", formData);

    submitSuccess.value = true;
    setTimeout(() => {
      resetForm();
      submitSuccess.value = false;
    }, 1500);
  } catch (err) {
    console.error("Erreur:", err);
  } finally {
    isSubmitting.value = false;
  }
};

// Récupération des demandes utilisateur
const getDemandes = () => {
  axiosInstance
    .get("user-demandes")
    .then(({ data }) => (demandes.value = data))
    .catch((err) => console.error(err));
};

// Réinitialisation
const resetForm = () => {
  requestForm.demande_id = null;
  requestForm.type = "";
  requestForm.description = "";
  requestForm.nom = "";
  requestForm.email = "";
  selectedFiles.value = [];
};
</script>
