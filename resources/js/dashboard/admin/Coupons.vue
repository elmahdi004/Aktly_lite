<template>
    <div class="coupons-management">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="page-title">
                        <i class="fas fa-ticket-alt"></i>
                        {{ t("coupons.title") }}
                    </h1>
                    <p class="page-subtitle">{{ t("coupons.subtitle") }}</p>
                </div>
                <button @click="openCreateModal" class="btn-primary-gradient">
                    <i class="fas fa-plus"></i>
                    {{ t("coupons.createNew") }}
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">{{ t("coupons.totalCoupons") }}</p>
                        <h3 class="stat-value">{{ statistics.total_coupons || 0 }}</h3>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">{{ t("coupons.activeCoupons") }}</p>
                        <h3 class="stat-value">{{ statistics.active_coupons || 0 }}</h3>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">{{ t("coupons.totalUsers") }}</p>
                        <h3 class="stat-value">{{ statistics.total_users || 0 }}</h3>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="stat-info">
                        <p class="stat-label">{{ t("coupons.dossiersUsed") }}</p>
                        <h3 class="stat-value">{{ statistics.total_dossiers_used || 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input
                    v-model="filters.search"
                    type="text"
                    :placeholder="t('coupons.searchPlaceholder')"
                    @input="debouncedSearch"
                />
            </div>

            <div class="filter-buttons">
                <button
                    v-for="status in statusFilters"
                    :key="status.value"
                    :class="['filter-btn', { active: filters.status === status.value }]"
                    @click="filters.status = status.value; loadCoupons()"
                >
                    <i :class="status.icon"></i>
                    {{ t(status.label) }}
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>{{ t("coupons.loading") }}</p>
        </div>

        <!-- Coupons Table -->
        <div v-else-if="coupons.length > 0" class="table-container">
            <table class="coupons-table">
                <thead>
                    <tr>
                        <th>{{ t("coupons.code") }}</th>
                        <th>{{ t("coupons.description") }}</th>
                        <th>{{ t("coupons.status") }}</th>
                        <th>{{ t("coupons.expires") }}</th>
                        <th>{{ t("coupons.usage") }}</th>
                        <th>{{ t("coupons.dossiers") }}</th>
                        <th>{{ t("coupons.actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="coupon in coupons"
                        :key="coupon.id"
                        class="coupon-row"
                    >
                        <td>
                            <div class="code-cell">
                                <span class="code-badge">{{ coupon.code }}</span>
                                <button
                                    @click="copyCouponCode(coupon.code)"
                                    class="copy-btn"
                                    :title="t('coupons.copyCode')"
                                >
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="description-text">{{
                                coupon.description || t("coupons.noDescription")
                            }}</span>
                        </td>
                        <td>
                            <span
                                :class="[
                                    'status-badge',
                                    getStatusClass(coupon),
                                ]"
                            >
                                <i :class="getStatusIcon(coupon)"></i>
                                {{ getStatusText(coupon) }}
                            </span>
                        </td>
                        <td>
                            <span class="date-text">{{
                                formatDate(coupon.expires_at)
                            }}</span>
                        </td>
                        <td>
                            <div class="usage-cell">
                                <div class="usage-bar-container">
                                    <div
                                        class="usage-bar"
                                        :style="{
                                            width: getUsagePercentage(
                                                coupon.total_users,
                                                coupon.max_users
                                            ),
                                        }"
                                    ></div>
                                </div>
                                <span class="usage-text">
                                    {{ coupon.total_users || 0 }}
                                    <span v-if="coupon.max_users"
                                        >/ {{ coupon.max_users }}</span
                                    >
                                    <span v-else>/ ∞</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="dossiers-cell">
                                <span class="dossiers-count">
                                    {{ coupon.total_dossiers || 0 }}
                                    <span v-if="coupon.max_dossiers"
                                        >/ {{ coupon.max_dossiers }}</span
                                    >
                                    <span v-else>/ ∞</span>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <button
                                    @click="viewCouponDetails(coupon)"
                                    class="action-btn view-btn"
                                    :title="t('coupons.view')"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button
                                    @click="editCoupon(coupon)"
                                    class="action-btn edit-btn"
                                    :title="t('coupons.edit')"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    @click="toggleCouponStatus(coupon)"
                                    class="action-btn toggle-btn"
                                    :title="
                                        coupon.active
                                            ? t('coupons.deactivate')
                                            : t('coupons.activate')
                                    "
                                >
                                    <i
                                        :class="
                                            coupon.active
                                                ? 'fas fa-toggle-on'
                                                : 'fas fa-toggle-off'
                                        "
                                    ></i>
                                </button>
                                <button
                                    @click="confirmDelete(coupon)"
                                    class="action-btn delete-btn"
                                    :title="t('coupons.delete')"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="pagination">
                <button
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="page-btn"
                >
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="page-info">
                    {{ t("coupons.page") }} {{ pagination.current_page }} {{
                        t("coupons.of")
                    }}
                    {{ pagination.last_page }}
                </span>
                <button
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="page-btn"
                >
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <i class="fas fa-ticket-alt empty-icon"></i>
            <h3>{{ t("coupons.noCoupons") }}</h3>
            <p>{{ t("coupons.noCouponsDesc") }}</p>
            <button @click="openCreateModal" class="btn-primary">
                <i class="fas fa-plus"></i>
                {{ t("coupons.createFirst") }}
            </button>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h2>
                        <i
                            :class="
                                editingCoupon
                                    ? 'fas fa-edit'
                                    : 'fas fa-plus-circle'
                            "
                        ></i>
                        {{
                            editingCoupon
                                ? t("coupons.editCoupon")
                                : t("coupons.createCoupon")
                        }}
                    </h2>
                    <button @click="closeModal" class="close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="saveCoupon" class="modal-form">
                    <!-- Coupon Code -->
                    <div class="form-group">
                        <label>
                            {{ t("coupons.couponCode") }}
                            <span class="required">*</span>
                        </label>
                        <div class="input-with-action">
                            <input
                                v-model="formData.code"
                                type="text"
                                :placeholder="t('coupons.codePlaceholder')"
                                required
                                maxlength="50"
                            />
                            <button
                                type="button"
                                @click="generateRandomCode"
                                class="btn-generate"
                            >
                                <i class="fas fa-random"></i>
                                {{ t("coupons.generate") }}
                            </button>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label>{{ t("coupons.description") }}</label>
                        <textarea
                            v-model="formData.description"
                            :placeholder="t('coupons.descPlaceholder')"
                            rows="3"
                            maxlength="255"
                        ></textarea>
                    </div>

                    <!-- Stripe Coupon ID -->
                    <div class="form-group">
                        <label>
                            {{ t("coupons.stripeCouponId") }}
                            <span class="optional">{{ t("coupons.optional") }}</span>
                        </label>
                        <input
                            v-model="formData.stripe_coupon_id"
                            type="text"
                            :placeholder="t('coupons.stripeIdPlaceholder')"
                        />
                        <small class="helper-text">{{
                            t("coupons.stripeHelper")
                        }}</small>
                    </div>

                    <div class="form-row">
                        <!-- Max Users -->
                        <div class="form-group">
                            <label>{{ t("coupons.maxUsers") }}</label>
                            <input
                                v-model.number="formData.max_users"
                                type="number"
                                min="1"
                                :placeholder="t('coupons.unlimited')"
                            />
                        </div>

                        <!-- Max Dossiers -->
                        <div class="form-group">
                            <label>{{ t("coupons.maxDossiers") }}</label>
                            <input
                                v-model.number="formData.max_dossiers"
                                type="number"
                                min="1"
                                :placeholder="t('coupons.unlimited')"
                            />
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    <div class="form-group">
                        <label>{{ t("coupons.expiryDate") }}</label>
                        <input
                            v-model="formData.expires_at"
                            type="datetime-local"
                            :min="minDateTime"
                        />
                    </div>

                    <!-- Active Toggle -->
                    <div class="form-group">
                        <label class="toggle-label">
                            <span class="toggle-block">
                                <input
                                    v-model="formData.active"
                                    type="checkbox"
                                    class="toggle-input"
                                />
                                <span class="toggle-switch"></span>
                            </span>
                            <span class="toggle-text">{{
                                t("coupons.activateImmediately")
                            }}</span>
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button
                            type="button"
                            @click="closeModal"
                            class="btn-secondary"
                        >
                            {{ t("coupons.cancel") }}
                        </button>
                        <button
                            type="submit"
                            class="btn-primary-gradient"
                            :disabled="saving"
                        >
                            <span v-if="saving">
                                <i class="fas fa-spinner fa-spin"></i>
                                {{ t("coupons.saving") }}
                            </span>
                            <span v-else>
                                <i class="fas fa-save"></i>
                                {{ t("coupons.save") }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Details Modal -->
        <div
            v-if="showDetailsModal"
            class="modal-overlay"
            @click="closeDetailsModal"
        >
            <div class="modal-content modal-details" @click.stop>
                <div class="modal-header">
                    <h2>
                        <i class="fas fa-info-circle"></i>
                        {{ t("coupons.couponDetails") }}
                    </h2>
                    <button @click="closeDetailsModal" class="close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div v-if="selectedCoupon" class="details-content">
                    <div class="detail-section">
                        <h3>{{ t("coupons.generalInfo") }}</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">{{
                                    t("coupons.code")
                                }}</span>
                                <span class="detail-value code-value">{{
                                    selectedCoupon.code
                                }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">{{
                                    t("coupons.status")
                                }}</span>
                                <span
                                    :class="[
                                        'status-badge',
                                        getStatusClass(selectedCoupon),
                                    ]"
                                >
                                    {{ getStatusText(selectedCoupon) }}
                                </span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">{{
                                    t("coupons.description")
                                }}</span>
                                <span class="detail-value">{{
                                    selectedCoupon.description ||
                                    t("coupons.noDescription")
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3>{{ t("coupons.usageStats") }}</h3>
                        <div class="stats-grid-small">
                            <div class="stat-item">
                                <i class="fas fa-users"></i>
                                <span class="stat-label">{{
                                    t("coupons.usersCount")
                                }}</span>
                                <span class="stat-value-small">
                                    {{ selectedCoupon.total_users || 0 }}
                                    <span v-if="selectedCoupon.max_users"
                                        >/ {{ selectedCoupon.max_users }}</span
                                    >
                                </span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-folder-open"></i>
                                <span class="stat-label">{{
                                    t("coupons.dossiersCount")
                                }}</span>
                                <span class="stat-value-small">
                                    {{ selectedCoupon.total_dossiers || 0 }}
                                    <span v-if="selectedCoupon.max_dossiers"
                                        >/ {{ selectedCoupon.max_dossiers }}</span
                                    >
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3>{{ t("coupons.dates") }}</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">{{
                                    t("coupons.created")
                                }}</span>
                                <span class="detail-value">{{
                                    formatDate(selectedCoupon.created_at)
                                }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">{{
                                    t("coupons.expires")
                                }}</span>
                                <span class="detail-value">{{
                                    formatDate(selectedCoupon.expires_at)
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="
                            selectedCoupon.usages &&
                            selectedCoupon.usages.length > 0
                        "
                        class="detail-section"
                    >
                        <h3>{{ t("coupons.recentUsage") }}</h3>
                        <div class="usage-list">
                            <div
                                v-for="usage in selectedCoupon.usages.slice(
                                    0,
                                    5
                                )"
                                :key="usage.id"
                                class="usage-item"
                            >
                                <i class="fas fa-user-circle"></i>
                                <div class="usage-info">
                                    <span class="usage-name">{{
                                        usage.user?.name
                                    }}</span>
                                    <span class="usage-email">{{
                                        usage.user?.email
                                    }}</span>
                                </div>
                                <span class="usage-dossiers">
                                    {{ usage.dossiers_used }}
                                    {{ t("coupons.dossiers") }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <transition name="toast">
            <div v-if="toast.show" :class="['toast', `toast-${toast.type}`]">
                <i :class="getToastIcon(toast.type)"></i>
                <span>{{ toast.message }}</span>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import axiosInstance from "@/axios";

const { t } = useI18n();

// State
const loading = ref(false);
const saving = ref(false);
const coupons = ref([]);
const statistics = ref({});
const pagination = ref({});
const showModal = ref(false);
const showDetailsModal = ref(false);
const editingCoupon = ref(null);
const selectedCoupon = ref(null);

// Filters
const filters = reactive({
    search: "",
    status: "all",
});

const statusFilters = [
    { value: "all", label: "coupons.allCoupons", icon: "fas fa-list" },
    { value: "active", label: "coupons.active", icon: "fas fa-check-circle" },
    {
        value: "inactive",
        label: "coupons.inactive",
        icon: "fas fa-times-circle",
    },
    {
        value: "expired",
        label: "coupons.expired",
        icon: "fas fa-calendar-times",
    },
];

// Form Data
const formData = reactive({
    code: "",
    description: "",
    expires_at: "",
    max_users: null,
    max_dossiers: null,
    stripe_coupon_id: "",
    active: true,
});

// Toast
const toast = reactive({
    show: false,
    message: "",
    type: "success",
});

// Computed
const minDateTime = computed(() => {
    return new Date().toISOString().slice(0, 16);
});

// Methods
const loadCoupons = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            search: filters.search,
            status: filters.status !== "all" ? filters.status : undefined,
        };
        const response = await axiosInstance.get("/admin/coupons", { params });
        coupons.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
        };
    } catch (error) {
        showToast(t("coupons.loadError"), "error");
    } finally {
        loading.value = false;
    }
};

const loadStatistics = async () => {
    try {
        const response = await axiosInstance.get("/admin/coupons/statistics");
        statistics.value = response.data;
    } catch (error) {
        console.error("Failed to load statistics:", error);
    }
};

const openCreateModal = () => {
    editingCoupon.value = null;
    resetForm();
    showModal.value = true;
};

const editCoupon = (coupon) => {
    editingCoupon.value = coupon;
    formData.code = coupon.code;
    formData.description = coupon.description || "";
    formData.expires_at = coupon.expires_at
        ? new Date(coupon.expires_at).toISOString().slice(0, 16)
        : "";
    formData.max_users = coupon.max_users;
    formData.max_dossiers = coupon.max_dossiers;
    formData.stripe_coupon_id = coupon.stripe_coupon_id || "";
    formData.active = coupon.active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const resetForm = () => {
    formData.code = "";
    formData.description = "";
    formData.expires_at = "";
    formData.max_users = null;
    formData.max_dossiers = null;
    formData.stripe_coupon_id = "";
    formData.active = true;
};

const saveCoupon = async () => {
    saving.value = true;
    try {
        if (editingCoupon.value) {
            await axiosInstance.put(
                `/admin/coupons/${editingCoupon.value.id}`,
                formData
            );
            showToast(t("coupons.updateSuccess"), "success");
        } else {
            await axiosInstance.post("/admin/coupons", formData);
            showToast(t("coupons.createSuccess"), "success");
        }
        closeModal();
        loadCoupons();
        loadStatistics();
    } catch (error) {
        const errorMsg =
            error.response?.data?.error || t("coupons.saveError");
        showToast(errorMsg, "error");
    } finally {
        saving.value = false;
    }
};

const generateRandomCode = async () => {
    try {
        const response = await axiosInstance.get("/admin/coupons/generate-code");
        formData.code = response.data.code;
    } catch (error) {
        showToast(t("coupons.generateError"), "error");
    }
};

const viewCouponDetails = async (coupon) => {
    try {
        const response = await axiosInstance.get(`/admin/coupons/${coupon.id}`);
        selectedCoupon.value = response.data;
        showDetailsModal.value = true;
    } catch (error) {
        showToast(t("coupons.detailsError"), "error");
    }
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedCoupon.value = null;
};

const toggleCouponStatus = async (coupon) => {
    try {
        await axiosInstance.post(`/admin/coupons/${coupon.id}/toggle`);
        showToast(t("coupons.statusUpdated"), "success");
        loadCoupons();
        loadStatistics();
    } catch (error) {
        showToast(t("coupons.statusError"), "error");
    }
};

const confirmDelete = async (coupon) => {
    if (confirm(t("coupons.deleteConfirm"))) {
        try {
            await axiosInstance.delete(`/admin/coupons/${coupon.id}`);
            showToast(t("coupons.deleteSuccess"), "success");
            loadCoupons();
            loadStatistics();
        } catch (error) {
            showToast(t("coupons.deleteError"), "error");
        }
    }
};

const copyCouponCode = (code) => {
    navigator.clipboard.writeText(code);
    showToast(t("coupons.codeCopied"), "success");
};

const changePage = (page) => {
    loadCoupons(page);
};

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadCoupons();
    }, 500);
};

// Helper functions
const getStatusClass = (coupon) => {
    if (!coupon.active) return "status-inactive";
    if (coupon.is_expired) return "status-expired";
    return "status-active";
};

const getStatusIcon = (coupon) => {
    if (!coupon.active) return "fas fa-times-circle";
    if (coupon.is_expired) return "fas fa-calendar-times";
    return "fas fa-check-circle";
};

const getStatusText = (coupon) => {
    if (!coupon.active) return t("coupons.inactive");
    if (coupon.is_expired) return t("coupons.expired");
    return t("coupons.active");
};

const formatDate = (date) => {
    if (!date) return t("coupons.never");
    return new Date(date).toLocaleDateString("fr-FR", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getUsagePercentage = (used, max) => {
    if (!max) return "100%";
    const percentage = (used / max) * 100;
    return `${Math.min(percentage, 100)}%`;
};

const getToastIcon = (type) => {
    const icons = {
        success: "fas fa-check-circle",
        error: "fas fa-exclamation-circle",
        info: "fas fa-info-circle",
    };
    return icons[type] || icons.info;
};

const showToast = (message, type = "success") => {
    toast.message = message;
    toast.type = type;
    toast.show = true;
    setTimeout(() => {
        toast.show = false;
    }, 3000);
};

// Lifecycle
onMounted(() => {
    loadCoupons();
    loadStatistics();
});
</script>

<style scoped>
.coupons-management {
    --bg: #f6f2ec;
    --panel: #ffffff;
    --ink: #0f172a;
    --muted: #5b6475;
    --accent: #0ea5a4;
    --accent-dark: #0f766e;
    --accent-soft: #ccfbf1;
    --border: #e2e8f0;
    --warning: #b45309;
    --warning-soft: #fef3c7;
    --danger: #b91c1c;
    --danger-soft: #fee2e2;
    --info: #2563eb;
    --info-soft: #dbeafe;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    font-family: "Manrope", "Plus Jakarta Sans", sans-serif;
    color: var(--ink);
}

/* Header Section */
.header-section {
    margin-bottom: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #ecfeff 100%);
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-text h1 {
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-title i {
    color: var(--accent);
}

.page-subtitle {
    color: var(--muted);
    font-size: 1rem;
    margin: 0;
}

/* Statistics Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--panel);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    border: 1px solid var(--border);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
}

.stat-primary .stat-icon {
    background: linear-gradient(135deg, #0ea5a4 0%, #14b8a6 100%);
}

.stat-success .stat-icon {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
}

.stat-warning .stat-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-info .stat-icon {
    background: linear-gradient(135deg, #0ea5a4 0%, #0f766e 100%);
}

.stat-info {
    flex: 1;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 0.25rem;
    display: block;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0;
}

/* Filters Section */
.filters-section {
    background: var(--panel);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 260px;
    position: relative;
    display: flex;
    align-items: center;
}

.search-box i {
    position: absolute;
    left: 1rem;
    color: #94a3b8;
}

.search-box input {
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 2.75rem;
    border: 1px solid var(--border);
    border-radius: 999px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: var(--panel);
}

.search-box input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.15);
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.6rem 1.1rem;
    border: 1px solid var(--border);
    background: var(--panel);
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-btn:hover {
    border-color: var(--accent);
    color: var(--accent-dark);
    background: #e6fffb;
}

.filter-btn.active {
    border-color: var(--accent);
    background: var(--accent);
    color: white;
}

/* Table Container */
.table-container {
    background: var(--panel);
    border-radius: 1.25rem;
    border: 1px solid var(--border);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

.coupons-table {
    width: 100%;
    border-collapse: collapse;
}

.coupons-table thead {
    background: #f8fafc;
    color: #475569;
}

.coupons-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.coupons-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s ease;
}

.coupons-table tbody tr:hover {
    background: #f1f5f9;
}

.coupons-table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Code Cell */
.code-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.code-badge {
    font-family: "Monaco", "Courier New", monospace;
    background: #e6fffb;
    color: var(--accent-dark);
    padding: 0.4rem 0.8rem;
    border-radius: 0.6rem;
    font-weight: 700;
    font-size: 0.85rem;
}

.copy-btn {
    padding: 0.4rem;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    cursor: pointer;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.copy-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}

.status-active {
    background: #dcfce7;
    color: #15803d;
}

.status-inactive {
    background: var(--danger-soft);
    color: var(--danger);
}

.status-expired {
    background: var(--warning-soft);
    color: var(--warning);
}

/* Usage Cell */
.usage-cell {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.usage-bar-container {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}

.usage-bar {
    height: 100%;
    background: linear-gradient(90deg, #0ea5a4 0%, #14b8a6 100%);
    transition: width 0.3s ease;
}

.usage-text {
    font-size: 0.875rem;
    color: var(--muted);
}

.dossiers-cell {
    font-weight: 600;
    color: #475569;
}

/* Actions Cell */
.actions-cell {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.5rem;
    border: none;
    border-radius: 0.7rem;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn {
    background: var(--info-soft);
    color: var(--info);
}

.view-btn:hover {
    background: #3b82f6;
    color: white;
}

.edit-btn {
    background: var(--warning-soft);
    color: var(--warning);
}

.edit-btn:hover {
    background: #f59e0b;
    color: white;
}

.toggle-btn {
    background: #e6fffb;
    color: var(--accent-dark);
}

.toggle-btn:hover {
    background: var(--accent);
    color: white;
}

.delete-btn {
    background: var(--danger-soft);
    color: var(--danger);
}

.delete-btn:hover {
    background: #ef4444;
    color: white;
}

/* Buttons */
.btn-primary-gradient {
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #0ea5a4 0%, #14b8a6 100%);
    color: white;
    border: none;
    border-radius: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.btn-primary-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(14, 165, 164, 0.35);
}

.btn-primary {
    padding: 0.875rem 1.75rem;
    background: var(--accent-dark);
    color: white;
    border: none;
    border-radius: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-secondary {
    padding: 0.875rem 1.75rem;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
}

.page-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border);
    background: white;
    border-radius: 0.6rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-btn:not(:disabled):hover {
    border-color: var(--accent);
    background: #e6fffb;
}

.page-info {
    font-size: 0.95rem;
    color: var(--muted);
}

/* Loading */
.loading-container {
    text-align: center;
    padding: 4rem 2rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e2e8f0;
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--panel);
    border-radius: 1.25rem;
    border: 1px solid var(--border);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: var(--ink);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--muted);
    margin-bottom: 2rem;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background: var(--panel);
    border-radius: 1.5rem;
    max-width: 640px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid var(--border);
    box-shadow: 0 25px 70px rgba(15, 23, 42, 0.28);
}

.modal-details {
    max-width: 800px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #ffffff 0%, #ecfeff 100%);
}

.modal-header h2 {
    font-size: 1.5rem;
    color: var(--ink);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.close-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #f1f5f9;
    border-radius: 0.6rem;
    cursor: pointer;
    color: #475569;
    font-size: 1.25rem;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: #e2e8f0;
    color: #1a202c;
}

/* Modal Form */
.modal-form {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.required {
    color: #ef4444;
}

.optional {
    font-weight: 500;
    color: #94a3b8;
    font-size: 0.875rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid var(--border);
    border-radius: 0.9rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #ffffff;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(14, 165, 164, 0.15);
}

.input-with-action {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.input-with-action input {
    flex: 1;
}

.btn-generate {
    padding: 0.75rem 1.25rem;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid var(--border);
    border-radius: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-generate:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.helper-text {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--muted);
}

/* Toggle Label */
.toggle-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    flex-wrap: wrap;
}

.toggle-input {
    display: none;
}

.toggle-block {
    display: inline-flex;
    align-items: center;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 28px;
    background: #cbd5e1;
    border-radius: 14px;
    transition: background 0.3s ease;
    flex: 0 0 50px;
}

.toggle-text {
    display: inline-block;
    line-height: 1.25;
    color: var(--ink);
    font-weight: 600;
}

.toggle-switch::before {
    content: "";
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: white;
    top: 3px;
    left: 3px;
    transition: transform 0.3s ease;
}

.toggle-input:checked + .toggle-switch {
    background: var(--accent);
}

.toggle-input:checked + .toggle-switch::before {
    transform: translateX(22px);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    flex-wrap: wrap;
}

/* Details Content */
.details-content {
    padding: 2rem;
}

.detail-section {
    margin-bottom: 2rem;
}

.detail-section:last-child {
    margin-bottom: 0;
}

.detail-section h3 {
    font-size: 1.125rem;
    color: var(--ink);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.875rem;
    color: var(--muted);
    font-weight: 600;
}

.detail-value {
    font-size: 1rem;
    color: var(--ink);
    font-weight: 700;
}

.code-value {
    font-family: "Monaco", "Courier New", monospace;
    color: var(--accent-dark);
}

.stats-grid-small {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-item {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.9rem;
    text-align: center;
    border: 1px solid var(--border);
}

.stat-item i {
    font-size: 2rem;
    color: var(--accent);
    margin-bottom: 0.5rem;
}

.stat-item .stat-label {
    font-size: 0.875rem;
    color: var(--muted);
    margin-bottom: 0.5rem;
}

.stat-value-small {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--ink);
}

.usage-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.usage-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.9rem;
    border: 1px solid var(--border);
}

.usage-item i {
    font-size: 1.5rem;
    color: var(--accent);
}

.usage-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.usage-name {
    font-weight: 600;
    color: var(--ink);
}

.usage-email {
    font-size: 0.875rem;
    color: var(--muted);
}

.usage-dossiers {
    font-weight: 700;
    color: var(--accent-dark);
    font-size: 0.875rem;
}

/* Toast */
.toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    border-radius: 0.9rem;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 12px 34px rgba(15, 23, 42, 0.25);
    z-index: 2000;
    font-weight: 600;
}

.toast-success {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
}

.toast-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.toast-info {
    background: linear-gradient(135deg, #0ea5a4 0%, #0f766e 100%);
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateY(20px);
    opacity: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .coupons-management {
        padding: 1rem;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filters-section {
        flex-direction: column;
    }

    .search-box {
        min-width: 100%;
    }

    .table-container {
        overflow-x: auto;
    }

    .coupons-table {
        min-width: 900px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid-small {
        grid-template-columns: 1fr;
    }

    .toast {
        left: 1rem;
        right: 1rem;
    }
}

@media (max-width: 480px) {
    .header-text h1 {
        font-size: 1.5rem;
    }

    .page-subtitle {
        font-size: 0.875rem;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }
}
</style>
