<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">
                {{ isNl ? 'Gebruikers' : 'Utilisateurs' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ isNl ? 'Beheer en exporteer de geregistreerde gebruikers.' : 'Gérez et exportez les utilisateurs inscrits.' }}
            </p>
        </div>

        <!-- filters -->
        <div class="glass-card flex flex-col gap-4 p-5 lg:flex-row lg:flex-wrap lg:items-end">
            <div class="flex flex-col">
                <label class="mb-1 text-xs font-medium text-slate-500">{{ isNl ? 'Periode' : 'Période' }}</label>
                <select v-model="filters.period" class="ad-input">
                    <option value="all">{{ isNl ? 'Alle' : 'Toutes' }}</option>
                    <option value="day">{{ isNl ? 'Laatste 24u' : 'Dernière 24h' }}</option>
                    <option value="week">{{ isNl ? '7 dagen' : '7 derniers jours' }}</option>
                    <option value="month">{{ isNl ? '30 dagen' : '30 derniers jours' }}</option>
                    <option value="year">{{ isNl ? '12 maanden' : '12 derniers mois' }}</option>
                </select>
            </div>
            <div class="flex flex-col">
                <label class="mb-1 text-xs font-medium text-slate-500">{{ isNl ? 'Taal' : 'Langue' }}</label>
                <select v-model="filters.langue_user" class="ad-input">
                    <option value="">{{ isNl ? 'Alle' : 'Toutes' }}</option>
                    <option value="fr">{{ isNl ? 'Frans' : 'Français' }}</option>
                    <option value="nl">{{ isNl ? 'Nederlands' : 'Néerlandais' }}</option>
                </select>
            </div>
            <div class="flex flex-col">
                <label class="mb-1 text-xs font-medium text-slate-500">{{ isNl ? 'Zoeken' : 'Recherche' }}</label>
                <input v-model="filters.search" type="text" :placeholder="isNl ? 'Naam of e-mail' : 'Nom ou email'" class="ad-input w-56" />
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="fetchUsers" class="ad-btn-primary">{{ isNl ? 'Filteren' : 'Filtrer' }}</button>
                <button @click="resetFilters" class="ad-btn-ghost">{{ isNl ? 'Reset' : 'Réinitialiser' }}</button>
            </div>
            <div class="flex flex-wrap gap-2 lg:ml-auto">
                <button @click="selectAllPage" :disabled="!users.data || users.data.length === 0" class="ad-btn-ghost disabled:opacity-40">{{ isNl ? 'Selecteer pagina' : 'Sélect. page' }}</button>
                <button :disabled="!selectedIds.length" @click="copyEmails" class="ad-btn-ghost disabled:opacity-40">Emails</button>
                <button :disabled="!selectedIds.length" @click="copyNameEmail" class="ad-btn-ghost disabled:opacity-40">{{ isNl ? 'Naam+Email' : 'Nom+Email' }}</button>
                <button :disabled="!selectedIds.length" @click="copyCSV" class="ad-btn-ghost disabled:opacity-40">CSV</button>
            </div>
        </div>

        <!-- table -->
        <section class="glass-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3"><input type="checkbox" :checked="allSelected" @change="toggleAll" class="accent-emerald-600" /></th>
                            <th class="px-4 py-3 font-semibold">{{ isNl ? 'Naam' : 'Nom' }}</th>
                            <th class="px-4 py-3 font-semibold">Email</th>
                            <th class="px-4 py-3 font-semibold">{{ isNl ? 'Taal' : 'Langue' }}</th>
                            <th class="px-4 py-3 font-semibold">{{ isNl ? 'Dossiers' : 'Dossiers' }}</th>
                            <th class="px-4 py-3 font-semibold">{{ isNl ? 'Aangemaakt' : 'Créé' }}</th>
                            <th class="px-4 py-3 font-semibold">{{ isNl ? 'Leeftijd' : 'Âge' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-t border-slate-100 transition hover:bg-emerald-50/40">
                            <td class="px-4 py-3"><input type="checkbox" :value="user.id" v-model="selectedIds" class="accent-emerald-600" /></td>
                            <td class="max-w-[200px] truncate px-4 py-3 font-medium text-slate-700" :title="user.name">{{ user.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ user.email }}</td>
                            <td class="px-4 py-3 uppercase text-slate-500">{{ user.langue_user || '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ user.cumulative_dossier_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ formatDate(user.created_at) }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ accountAge(user.created_at) }}j</td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">{{ isNl ? 'Laden…' : 'Chargement…' }}</td>
                        </tr>
                        <tr v-if="!loading && users.data && users.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">{{ isNl ? 'Geen gebruikers gevonden.' : 'Aucun utilisateur trouvé.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- pagination -->
        <div v-if="!loading && users.data && (users.meta || users.data.length > 0)" class="glass-card flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="text-sm text-slate-500">
                    <template v-if="users.meta">
                        <span class="font-semibold text-slate-700">{{ getStartIndex() }}-{{ getEndIndex() }}</span>
                        {{ isNl ? 'van' : 'sur' }} <span class="font-semibold text-slate-700">{{ users.meta.total }}</span>
                    </template>
                    <span v-if="selectedIds.length" class="ml-2 text-emerald-600">| {{ selectedIds.length }} {{ isNl ? 'geselecteerd' : 'sélectionné(s)' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-slate-500">{{ isNl ? 'Tonen:' : 'Afficher:' }}</label>
                    <select v-model.number="filters.per_page" @change="filters.page = 1; fetchUsers()" class="ad-input">
                        <option :value="20">20</option><option :value="50">50</option><option :value="100">100</option><option :value="200">200</option>
                    </select>
                </div>
            </div>
            <div v-if="users.meta && users.meta.last_page > 1" class="flex items-center gap-1">
                <button @click="goTo(users.meta.current_page - 1)" :disabled="users.meta.current_page === 1" class="ad-page disabled:opacity-40">‹</button>
                <template v-for="page in getPageNumbers()" :key="page">
                    <button v-if="page !== '...'" @click="goTo(page)" class="ad-page" :class="page === users.meta.current_page ? 'bg-emerald-600 text-white border-emerald-600' : ''">{{ page }}</button>
                    <span v-else class="px-2 text-slate-400">…</span>
                </template>
                <button @click="goTo(users.meta.current_page + 1)" :disabled="users.meta.current_page === users.meta.last_page" class="ad-page disabled:opacity-40">›</button>
            </div>
        </div>
        <transition name="fade"><div v-if="copiedMessage" class="text-xs font-medium text-emerald-600">{{ copiedMessage }}</div></transition>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axiosInstance from '@/axios';

const { locale } = useI18n();
const isNl = computed(() => locale.value === 'nl');

const users = ref({ data: [] });
const loading = ref(false);
const selectedIds = ref([]);
const copiedMessage = ref('');

const filters = reactive({ period: 'all', langue_user: '', search: '', page: 1, per_page: 20 });

const allSelected = computed(() => users.value.data && users.value.data.length > 0 && selectedIds.value.length === users.value.data.length);

const fetchUsers = async () => {
    loading.value = true;
    try {
        const { data } = await axiosInstance.get('/admin/users', { params: { ...filters } });
        users.value = {
            data: data.data || [],
            meta: { current_page: data.current_page || 1, last_page: data.last_page || 1, per_page: data.per_page || 20, total: data.total || 0 },
        };
    } catch (e) {
        console.error('Error fetching users:', e);
        users.value = { data: [], meta: null };
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.period = 'all'; filters.langue_user = ''; filters.search = ''; filters.page = 1;
    fetchUsers();
};

watch(() => [filters.period, filters.langue_user, filters.search], () => { filters.page = 1; });

const goTo = (page) => {
    if (page < 1 || page > (users.value.meta?.last_page || 1)) return;
    filters.page = page; fetchUsers();
};

const getStartIndex = () => users.value.meta ? (users.value.meta.current_page - 1) * users.value.meta.per_page + 1 : 0;
const getEndIndex = () => users.value.meta ? Math.min(users.value.meta.current_page * users.value.meta.per_page, users.value.meta.total) : 0;

const getPageNumbers = () => {
    if (!users.value.meta) return [];
    const current = users.value.meta.current_page, last = users.value.meta.last_page, pages = [];
    if (last <= 7) { for (let i = 1; i <= last; i++) pages.push(i); }
    else {
        pages.push(1);
        if (current <= 4) { for (let i = 2; i <= 5; i++) pages.push(i); pages.push('...', last); }
        else if (current >= last - 3) { pages.push('...'); for (let i = last - 4; i <= last; i++) pages.push(i); }
        else { pages.push('...'); for (let i = current - 1; i <= current + 1; i++) pages.push(i); pages.push('...', last); }
    }
    return pages;
};

const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString(isNl.value ? 'nl-BE' : 'fr-BE') : '—';
const accountAge = (iso) => iso ? Math.floor((Date.now() - new Date(iso).getTime()) / 86400000) : 0;

const toggleAll = () => { selectedIds.value = allSelected.value ? [] : users.value.data.map((u) => u.id); };
const selectAllPage = () => { if (users.value.data) selectedIds.value = users.value.data.map((u) => u.id); };

const selectedUsers = () => users.value.data.filter((u) => selectedIds.value.includes(u.id));
const copy = (text, label) => navigator.clipboard.writeText(text).then(() => { copiedMessage.value = label + (isNl.value ? ' gekopieerd' : ' copié(s)'); setTimeout(() => (copiedMessage.value = ''), 2000); });
const copyEmails = () => copy(selectedUsers().map((u) => u.email).join('\n'), 'Emails');
const copyNameEmail = () => copy(selectedUsers().map((u) => `${u.name} <${u.email}>`).join('\n'), isNl.value ? 'Naam+Email' : 'Nom+Email');
const escapeCsv = (val) => '"' + String(val ?? '').replace(/"/g, '""') + '"';
const copyCSV = () => {
    const header = 'id,name,email,langue_user,cumulative_dossier_count,created_at';
    const rows = selectedUsers().map((u) => [u.id, escapeCsv(u.name), u.email, u.langue_user, u.cumulative_dossier_count, u.created_at].join(','));
    copy([header, ...rows].join('\n'), 'CSV');
};

fetchUsers();
</script>

<style scoped>
.ad-input {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    background: #fff;
    color: #334155;
}
.ad-input:focus { outline: none; border-color: #34d399; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
.ad-btn-primary {
    display: inline-flex; align-items: center; gap: 0.375rem;
    border-radius: 0.75rem; background: #059669; color: #fff;
    padding: 0.5rem 0.9rem; font-size: 0.875rem; font-weight: 600; transition: background 0.2s;
}
.ad-btn-primary:hover { background: #047857; }
.ad-btn-ghost {
    display: inline-flex; align-items: center; gap: 0.375rem;
    border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; color: #475569;
    padding: 0.5rem 0.9rem; font-size: 0.875rem; font-weight: 600; transition: all 0.2s;
}
.ad-btn-ghost:hover:not(:disabled) { border-color: #6ee7b7; color: #047857; }
.ad-page {
    min-width: 2.25rem; padding: 0.4rem 0.6rem; font-size: 0.875rem; font-weight: 600;
    border: 1px solid #e2e8f0; border-radius: 0.6rem; background: #fff; color: #475569; transition: all 0.15s;
}
.ad-page:hover:not(:disabled) { background: #f8fafc; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
