import "./bootstrap";
import "@fortawesome/fontawesome-free/css/all.css";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./routes.js";
import { createPinia } from "pinia";
import { useAuthStore } from "./pinia.js";

import i18n from "./i18n.js";
const app = createApp(App);
const pinia = createPinia();
app.use(pinia);
app.use(router);
const authStore = useAuthStore();
const token = localStorage.getItem("token");
if (token) {
    authStore
        .checkAuthStatus()
        .then(() => {
            const userLanguage = authStore.user?.langue_user || "fr"; // Langue par défaut : 'fr'
            localStorage.setItem("lang", userLanguage);
            i18n.global.locale = userLanguage;
        })
        .catch(() => {
            // Ne rien faire si l'utilisateur n'est pas connecté
        });
}
app.use(i18n);

app.mount("#app");
