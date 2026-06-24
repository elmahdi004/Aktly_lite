<template>
  <div class="mx-auto w-full max-w-lg animate-fade-up">
    <!-- PageHeader -->
    <div class="mb-7 text-center">
      <div class="brand-gradient mx-auto mb-4 grid h-12 w-12 place-items-center rounded-2xl">
        <KeyRound class="h-6 w-6 text-white" />
      </div>
      <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
        Modifier le mot de passe
      </h1>
      <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
        Choisissez un nouveau mot de passe sûr pour protéger votre compte.
      </p>
    </div>

    <div class="glass-card p-6 sm:p-8">
      <!-- StatusAlert : success -->
      <div v-if="message" class="mb-5 flex items-center gap-2.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 animate-fade-up">
        <CheckCircle2 class="h-4 w-4 shrink-0" />
        <span>{{ message }}</span>
      </div>

      <!-- StatusAlert : error -->
      <div v-if="errorMessage" class="mb-5 flex items-center gap-2.5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-600 animate-fade-up">
        <AlertCircle class="h-4 w-4 shrink-0" />
        <span>{{ errorMessage }}</span>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <!-- current password -->
        <div class="animate-fade-up-1">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Ancien mot de passe</label>
          <div class="relative">
            <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
            <input
              v-model="currentPassword"
              :type="showCurrent ? 'text' : 'password'"
              placeholder="Ancien mot de passe"
              class="input-aurora pl-11 pr-11"
              :class="errors.current_password ? '!border-red-400 focus:!ring-red-400/15' : ''"
            />
            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
              <component :is="showCurrent ? EyeOff : Eye" class="h-[18px] w-[18px]" />
            </button>
          </div>
          <p v-if="errors.current_password" class="mt-1 text-xs font-medium text-rose-500">{{ errors.current_password }}</p>
        </div>

        <!-- new password -->
        <div class="animate-fade-up-2">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
          <div class="relative">
            <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
            <input
              v-model="newPassword"
              :type="showNew ? 'text' : 'password'"
              placeholder="Nouveau mot de passe"
              class="input-aurora pl-11 pr-11"
              :class="errors.new_password ? '!border-red-400 focus:!ring-red-400/15' : ''"
            />
            <button type="button" @click="showNew = !showNew" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
              <component :is="showNew ? EyeOff : Eye" class="h-[18px] w-[18px]" />
            </button>
          </div>
          <p v-if="errors.new_password" class="mt-1 text-xs font-medium text-rose-500">{{ errors.new_password }}</p>
        </div>

        <!-- confirm new password -->
        <div class="animate-fade-up-3">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirmer le nouveau mot de passe</label>
          <div class="relative">
            <Lock class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
            <input
              v-model="newPasswordConfirmation"
              :type="showConfirm ? 'text' : 'password'"
              placeholder="Confirmer le nouveau mot de passe"
              class="input-aurora pl-11 pr-11"
            />
            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
              <component :is="showConfirm ? EyeOff : Eye" class="h-[18px] w-[18px]" />
            </button>
          </div>
        </div>

        <button type="submit" :disabled="loading" class="btn-aurora w-full">
          <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
          <span>{{ loading ? 'En cours...' : 'Enregistrer' }}</span>
          <ArrowRight v-if="!loading" class="h-4 w-4" />
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axiosInstance from "@/axios.js";
import { KeyRound, Lock, Eye, EyeOff, ArrowRight, CheckCircle2, AlertCircle } from "lucide-vue-next";

const currentPassword = ref("");
const newPassword = ref("");
const newPasswordConfirmation = ref("");
const loading = ref(false);
const message = ref("");
const errorMessage = ref("");
const errors = ref({});

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const submit = async () => {
  message.value = "";
  errorMessage.value = "";
  errors.value = {};

  if (!currentPassword.value || !newPassword.value || !newPasswordConfirmation.value) {
    errorMessage.value = "Veuillez remplir tous les champs.";
    return;
  }
  if (newPassword.value !== newPasswordConfirmation.value) {
    errors.value.new_password = "La confirmation ne correspond pas.";
    return;
  }

  try {
    loading.value = true;
    await axiosInstance.post("/change-password", {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    });
    message.value = "Mot de passe mis à jour avec succès.";
    currentPassword.value = "";
    newPassword.value = "";
    newPasswordConfirmation.value = "";
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(err.response.data.errors).map(([k, v]) => [k, v[0]])
      );
    } else {
      errorMessage.value = "Une erreur est survenue.";
    }
  } finally {
    loading.value = false;
  }
};
</script>
