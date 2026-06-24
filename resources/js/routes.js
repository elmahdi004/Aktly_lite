import { createWebHistory, createRouter } from "vue-router";
import Login from "./auth/login.vue";
import ForgotPassword from "./auth/ForgotPassword.vue";
import ResetPassword from "./auth/ResetPassword.vue";
import Register from "./auth/Register.vue";
import Step1 from "./steps/Step1.vue";
import Step2 from "./steps/Step2.vue";
import Steps from "./steps/Steps.vue";
import Dashboard from "./dashboard/Dashboard.vue";
import DashboardAdmin from "./dashboard/admin/Index.vue";
import ServicesAdmin from "./dashboard/admin/Services.vue";
import SupportAdmin from "./dashboard/admin/Support.vue";
import SettingsAdmin from "./dashboard/admin/Settings.vue";
import addrssService from "./services/AddrssService.vue";
import Index from "./form/Index.vue";
import { useAuthStore } from "./pinia.js";
import axiosInstance from "./axios";
import MyAssistance from "./dashboard/client/MyAssistance.vue";
import MesDemandes from "./dashboard/client/MesDemandes.vue";
import IndexClient from "./dashboard/client/Index.vue";
import CreateTicket from "./dashboard/client/CreateTicket.vue";
import Users from "./dashboard/admin/Users.vue";
import Coupons from "./dashboard/admin/Coupons.vue";
import CheckoutSuccess from "./subscription/CheckoutSuccess.vue";
import CheckoutFailure from "./subscription/CheckoutFailure.vue";

const routes = [
    {
        path: "/",
        name: "steps",
        component: Steps,
        children: [
            {
                path: "/",
                name: "step1",
                component: Step1,
            },
            {
                path: "/step2",
                name: "step2",
                component: Step2,
            },
            {
                path: "/address_service",
                name: "addressService",
                component: addrssService,
            },
        ],
    },
    {
        path: "/login/:lang?",
        name: "login",
        component: Login,
    },
    {
        path: "/register/:lang?",
        name: "register",
        component: Register,
    },
    {
        path: "/forgotpassword",
        name: "forgotpassword",
        component: ForgotPassword,
    },
    {
        path: "/reset-password/:token",
        name: "password.reset",
        component: ResetPassword,
        props: true, // permet de passer token et email en props
    },

    {
        path: "/verify-email/:token",
        name: "verifyEmail",
        component: () => import("./auth/VerifyEmail.vue"),
        props: true,
    },
    {
        path: "/dashboard",
        name: "dashboard",
        component: Dashboard,
        // alias: "/admin",
        children: [
            {
                path: "/admin",
                name: "admin",
                component: DashboardAdmin,
                meta: { requiresAdmin: true },
            },
            {
                path: "admin/services",
                name: "adminServices",
                component: ServicesAdmin,
                meta: { requiresAdmin: true },
            },
            {
                path: "admin/services",
                name: "adminSupport",
                component: SupportAdmin,
                meta: { requiresAdmin: true },
            },
            {
                path: "admin/settings",
                name: "adminSettings",
                component: SettingsAdmin,
                meta: { requiresAdmin: true },
            },
            {
                path: "/admin/users",
                name: "adminUsers",
                component: Users,
                meta: { requiresAdmin: true },
            },
            {
                path: "/admin/coupons",
                name: "adminCoupons",
                component: Coupons,
                meta: { requiresAdmin: true },
            },
            {
                path: "/assistance",
                name: "assistance",
                component: MyAssistance,
            },
            {
                path: "/mes-demandes",
                name: "mesDemandes",
                component: MesDemandes,
            },
            {
                path: "/client",
                name: "client",
                component: IndexClient,
            },
            {
                path: "/demande-modifier/:id?",
                name: "demandeModify",
                component: () => import("./dashboard/client/ModifyDemande.vue"),
                meta: { requiresAuth: true },
            },
            {
                path: "/create-reclamation",
                name: "createTicket",
                component: CreateTicket,
            },
            {
                path: "/form",
                name: "form",
                component: Index,
            },
            {
                path: "/buy-credits",
                name: "buyCredits",
                component: () => import("./components/SubscriptionPlans.vue"), // lazy-loaded
                meta: { requiresAuth: true }, // protect behind login
            },
            {
                path: "/subscription/success",
                name: "subscriptionSuccess",
                component: CheckoutSuccess,
                meta: { requiresAuth: true },
            },
            {
                path: "/subscription/fail",
                name: "subscriptionFailure",
                component: CheckoutFailure,
                meta: { requiresAuth: true },
            },
            {
                path: "/profile",
                name: "profile",
                component: () => import("./components/ProfileView.vue"),
                meta: { requiresAuth: true }, // optional: reuse your auth guard
            },
            {
                path: "/billing/checkout",
                name: "billingCheckout",
                component: () =>
                    import("./components/SubscriptionCheckout.vue"),
                meta: { requiresAuth: true },
            },
            {
                path: "/change-password",
                name: "changePassword",
                component: () => import("./components/ChangePassword.vue"),
                meta: { requiresAuth: true },
            },
        ],
    },
    {
        path: "/buy-credits/success",
        name: "paySuccess",
        component: () => import("./components/PaySuccess.vue"), // public
    },
    {
        path: "/buy-credits/cancel",
        name: "payCancel",
        component: () => import("./components/PayCancel.vue"), // public
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const publicRoutes = new Set([
        "login",
        "register",
        "forgotpassword",
        "password.reset",
        "idanalyzeruser",
        "verifyEmail",
    ]);
    const publicPathPrefixes = [
        "/login",
        "/register",
        "/forgotpassword",
        "/idanalyzer/user",
        "/verify-email",
        "/reset-password",
        "/veriffuser",
    ];
    const isPublicRoute =
        publicRoutes.has(to.name) ||
        publicPathPrefixes.some((prefix) => to.path.startsWith(prefix));
    const token = localStorage.getItem("token");
    const authStore = useAuthStore();

    if (isPublicRoute) {
        return next();
    }

    if (!token) {
        console.log("Not authenticated, redirecting to login.");
        return next({ name: "login", params: { lang: "fr" } });
    }

    // Hydrate auth store on refresh if token exists but store is empty
    if (token && !authStore.user) {
        try {
            await authStore.checkAuthStatus();
        } catch (e) {
            console.warn("Failed to hydrate auth store", e);
            localStorage.removeItem("token");
            return next({ name: "login", params: { lang: "fr" } });
        }
    }

    // If token exists but user is still not resolved, force login
    if (token && !authStore.user) {
        localStorage.removeItem("token");
        return next({ name: "login", params: { lang: "fr" } });
    }

    if (to.meta.requiresAdmin && !authStore.isAdmin) {
        console.log("Admin access required, redirecting to login.");
        return next({ name: "login", params: { lang: "fr" } });
    }

    const subscriptionGateRoutes = new Set([
        "step1",
        "step2",
        "addressService",
    ]);

    if (subscriptionGateRoutes.has(to.name)) {
        try {
            const queryDemandeId = to.query?.demande_id
                ? String(to.query.demande_id)
                : null;
            const storedDemandeId = sessionStorage.getItem("demande_id");
            const resumeDemandeId = queryDemandeId || storedDemandeId;

            // Resume flow: if demande exists and belongs to current user, allow access
            // even when credits are already consumed.
            if (resumeDemandeId) {
                try {
                    const { data: demandeData } = await axiosInstance.get(
                        `/demandes/${resumeDemandeId}`,
                    );
                    if (demandeData?.demande) {
                        sessionStorage.setItem("demande_id", resumeDemandeId);
                        return next();
                    }
                } catch (_) {
                    // Not a valid resume id, continue with normal subscription/credit checks.
                }
            }

            const { data } = await axiosInstance.get("/billing/subscription");

            // Allow access if user has active subscription OR valid coupon OR credits
            const hasValidSubscription = data.active;
            const hasValidCredits = Number(data.credits || 0) > 0;

            // Check if coupon is valid: must have code AND not be expired
            const expiresAtDate = data.coupon_expires_at
                ? new Date(data.coupon_expires_at)
                : null;
            const now = new Date();
            const isCouponExpired = expiresAtDate ? expiresAtDate < now : true;

            const hasValidCoupon =
                data.applied_coupon_code &&
                data.coupon_expires_at &&
                !isCouponExpired &&
                data.coupon_dossiers_remaining > 0;

            console.log("Has valid coupon:", hasValidCoupon);
            console.log("Has valid credits:", hasValidCredits);
            console.log(
                "Will allow access:",
                hasValidSubscription || hasValidCoupon || hasValidCredits,
            );

            if (!hasValidSubscription && !hasValidCoupon && !hasValidCredits) {
                return next({
                    name: "buyCredits",
                    query: { msg: "subscription_required" },
                });
            }

            // Check dossiers remaining
            // - If coupon is active: must have remaining (coupon limits are strict)
            // - If subscription is active: allow even if remaining <= 0 (extra billing will apply)
            if (hasValidCoupon) {
                if (data.coupon_dossiers_remaining <= 0) {
                    return next({
                        name: "buyCredits",
                        query: { msg: "no_dossiers" },
                    });
                }
            } else if (hasValidCredits) {
                // One-time credits are available - allow creation and consume 1 credit per dossier server-side.
            } else if (hasValidSubscription) {
                // Subscription is active - allow creation even if remaining <= 0
                // Extra dossier billing will apply automatically
            }

            return next();
        } catch (e) {
            return next({
                name: "buyCredits",
                query: { msg: "subscription_required" },
            });
        }
    }

    next();
});

export default router;
