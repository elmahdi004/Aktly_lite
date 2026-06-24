/* resources/js/axios.js */
import axios from "axios";
// NOTE: intentionally NOT importing the router here. axios.js is a leaf dependency of
// pinia → most components → routes.js; importing the router back into axios creates a
// circular import (routes.js ⇄ axios.js) that breaks under Vite HMR. We redirect with
// window.location instead, which fully resets app state on auth failure anyway.

const apiBaseUrl =
    import.meta.env.VITE_API_BASE_URL || "/api"; // same-origin by default (works locally and in prod)

const axiosInstance = axios.create({
    baseURL: apiBaseUrl,
    // withCredentials: true            // enable if you rely on Sanctum cookies
});

/* ── Request: attach Bearer token ─────────────────────────────────── */
axiosInstance.interceptors.request.use((cfg) => {
    const token = localStorage.getItem("token");
    if (token) cfg.headers.Authorization = `Bearer ${token}`;
    return cfg;
});

/* ── Response: 402 → no credits, go to /buy-credits ───────────────── */
axiosInstance.interceptors.response.use(
    (resp) => resp,
    async (err) => {
        if (err.response?.status === 401) {
            const guestAllowedPathPrefixes = [
                "/login",
                "/register",
                "/forgotpassword",
                "/reset-password",
                "/idanalyzer/user",
                "/verify-email",
            ];
            const currentPath = window.location.pathname;
            const isGuestAllowed = guestAllowedPathPrefixes.some((prefix) =>
                currentPath.startsWith(prefix),
            );

            localStorage.removeItem("token");
            if (isGuestAllowed) {
                return Promise.reject(err);
            }
            window.location.href = "/login/fr";
        }
        if (err.response?.status === 402) {
            try {
                const token = localStorage.getItem("token");
                if (token) {
                    // Safety check: if user still has one-time credits, do not force redirect.
                    const { data } = await axios.get(
                        `${apiBaseUrl}/billing/subscription`,
                        {
                            headers: { Authorization: `Bearer ${token}` },
                        },
                    );
                    if (Number(data?.credits || 0) > 0) {
                        return Promise.reject(err);
                    }
                }
            } catch (_) {
                // Ignore fallback errors and proceed with redirect.
            }

            // Redirect to the one-shot payment page (hard redirect — no router import).
            if (!window.location.pathname.startsWith("/buy-credits")) {
                window.location.href = "/buy-credits";
            }
        }
        return Promise.reject(err);
    },
);

export default axiosInstance; // ← name unchanged
