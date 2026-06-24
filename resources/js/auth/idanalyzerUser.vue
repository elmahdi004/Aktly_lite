<template>
  <div class="w-full relative">
    <!-- Dropdown pour la langue -->
    <div class="absolute top-0 right-0">
      <div class="dropdown mt-4">
        <button
          class="btn dropdown-toggle border-[#059669] text-[#64748b] p-1 border rounded-md w-32"
          type="button"
          data-bs-toggle="dropdown"
          aria-expanded="false"
        >
          {{ currentLanguage }} <i class="fa-solid fa-globe"></i>
        </button>
        <ul class="dropdown-menu w-32">
          <li>
            <a
              class="dropdown-item text-[#64748b]"
              href="#"
              @click.prevent="changeLanguage('fr')"
            >
              Français
            </a>
          </li>
          <li>
            <a
              class="dropdown-item text-[#64748b]"
              href="#"
              @click.prevent="changeLanguage('nl')"
            >
              Nederlands
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Titre Vérification d'identité -->
    <div class="titles text-center mt-8">
      <h1 class="text-2xl font-bold">{{ $t('auth.veriff_title') }}</h1>
    </div>
    <p class="text-[#64748b] text-sm mt-4 text-center">
      {{ $t('auth.veriff_subtitle') }}
    </p>

    <!-- Formulaire -->
    <div class="grid grid-cols-12 gap-y-4 mt-5 w-[80%] md:w-[60%] mx-auto">
      <div class="flex flex-col gap-y-2 col-span-12">
        <label class="text-sm">{{ $t('auth.veriff_number') }}</label>
        <input
          v-model="mobile"
          type="tel"
          :placeholder="$t('auth.veriff_placeholder')"
          :class="[
            'placeholder:text-sm border border-gray-300 rounded-md p-2 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
            inputError.mobile ? 'border-red-500' : '',
          ]"
        />
      </div>
      <p class="col-span-12 text-[#64748b] text-sm mt-2 text-center">
            {{ $t('auth.already_verified') }}
          <router-link :to="loginLink" class="text-[#059669]">
            {{ $t('register.login') }}
            </router-link>

        </p>
      <div class="col-span-12 flex justify-center mt-2">
        <button
          @click="sendIdAnalyzerLink"
          class="mx-4 px-10 py-2 border rounded-full text-white bg-[#059669]"
        >
           {{ loading ? $t('login.loading') : $t('auth.forgot_button') }}
        </button>
      </div>

      <p v-if="message" class="col-span-12 text-center text-sm mt-2">
        {{ message }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { onMounted } from "vue";

const router = useRouter();

import { computed } from "vue";
const { t, locale } = useI18n();

const loginLink = computed(() => ({
  name: "login",
  params: { lang: locale.value }
}));

const mobile = ref("");
const message = ref("");
const inputError = ref({ email: false });
const currentLanguage = ref("Français");
const loading = ref(false);

onMounted(() => {
    const lang = locale.value ?? 'fr';
    console.log("Detected language:", lang);
    changeLanguage(lang);
    console.log(locale.value);
});

const getLocaleCode = (lang) => {
        return lang === 'Français' ? 'fr' : 'nl';
    };
const sendIdAnalyzerLink = async () => {
  try {
    loading.value = true;
    const internationalMobileRegex = /^\+[1-9]\d{7,14}$/;
    if (!internationalMobileRegex.test(mobile.value)) {
      message.value = t("auth.veriff_invalid_mobile");
      inputError.value.mobile = true;
      loading.value = false;
      return;
    }
    await axiosInstance.post("/idanalyzer/send-verification-link", { number: mobile.value, lang: getLocaleCode(currentLanguage.value) });
    message.value = t("auth.veriff_success");
  } catch (err) {
    message.value = t("auth.veriff_error");
    inputError.value.mobile = true;
  } finally {
    //delete token from local storage
    localStorage.removeItem("token");
    loading.value = false;
  }
};

const changeLanguage = (lang) => {
  locale.value = lang;
  currentLanguage.value = lang === "fr" ? "Français" : "Nederlands";
  router.push({ name: 'idanalyzeruser'});
};
</script>
