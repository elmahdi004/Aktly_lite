<template>
    <div class="dashboard">
        <div class="mb-6 bg-white rounded-lg p-4 shadow-sm flex flex-col md:flex-row md:items-end md:gap-4 gap-4">
            <div class="flex flex-col">
                <label class="text-xs text-gray-500 mb-1">Période</label>
                <select v-model="filters.period" class="border rounded-md px-2 py-1 text-sm">
                    <option value="all">Toutes</option>
                    <option value="day">Dernière 24h</option>
                    <option value="week">7 derniers jours</option>
                    <option value="month">30 derniers jours</option>
                    <option value="year">12 derniers mois</option>
                </select>
            </div>
            <div class="flex flex-col">
                <label class="text-xs text-gray-500 mb-1">Langue</label>
                <select v-model="filters.langue_user" class="border rounded-md px-2 py-1 text-sm">
                    <option value="">Toutes</option>
                    <option value="fr">Francais</option>
                    <option value="nl">Neerlandais</option>
                </select>
            </div>
            <div class="flex flex-col">
                <label class="text-xs text-gray-500 mb-1">Recherche</label>
                <input v-model="filters.search" type="text" placeholder="Nom ou email" class="border rounded-md px-2 py-1 text-sm w-52" />
            </div>
            <div class="flex gap-2 mt-2 md:mt-0 flex-wrap">
                <button @click="fetchUsers" class="px-3 py-1 bg-[#059669] text-white rounded-md text-sm">Filtrer</button>
                <button @click="resetFilters" class="px-3 py-1 border rounded-md text-sm">Réinitialiser</button>
            </div>
            <div class="flex gap-2 mt-4 md:mt-0 md:ml-auto flex-wrap">
                <button @click="selectAllPage" :disabled="!users.data || users.data.length===0 || selectedIds.length===users.data.length" class="px-3 py-1 border rounded-md text-sm disabled:opacity-40">Sélect. page</button>
                <button :disabled="selectedIds.length===0" @click="copyEmails" class="px-3 py-1 bg-[#059669] text-white rounded-md text-sm disabled:opacity-40">Copier Emails</button>
                <button :disabled="selectedIds.length===0" @click="copyNames" class="px-3 py-1 bg-[#059669] text-white rounded-md text-sm disabled:opacity-40">Copier Noms</button>
                <button :disabled="selectedIds.length===0" @click="copyNameEmail" class="px-3 py-1 bg-[#059669] text-white rounded-md text-sm disabled:opacity-40">Copier Nom+Email</button>
                <button :disabled="selectedIds.length===0" @click="copyCSV" class="px-3 py-1 bg-[#059669] text-white rounded-md text-sm disabled:opacity-40">Copier CSV</button>
            </div>
        </div>

        <TableSection :title="title" :headers="headers">
            <tr v-for="user in users.data" :key="user.id">
                <td>
                    <input type="checkbox" :value="user.id" v-model="selectedIds" />
                </td>
                <td>{{ user.id }}</td>
                <td class="max-w-[180px] truncate" :title="user.name">{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td class="uppercase">{{ user.langue_user || '-' }}</td>
                <td>{{ user.subscription_plan || '-' }}</td>
                <td>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[0.7rem] font-semibold uppercase tracking-wide"
                        :class="statusClasses(user.subscription_status)"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClasses(user.subscription_status)"></span>
                        {{ formatStatus(user.subscription_status) }}
                    </span>
                </td>
                <td>{{ user.cumulative_dossier_count ?? 0 }}</td>
                <td>{{ formatDate(user.created_at) }}</td>
                <td>{{ accountAge(user.created_at) }}j</td>
            </tr>
            <tr v-if="loading">
                <td colspan="10" class="text-center py-4 text-sm text-gray-500">Chargement...</td>
            </tr>
            <tr v-if="!loading && users.data && users.data.length===0">
                <td colspan="10" class="text-center py-4 text-sm text-gray-500">Aucun utilisateur trouvé.</td>
            </tr>
        </TableSection>

        <!-- Pagination -->
        <div v-if="!loading && users.data && (users.meta || users.data.length > 0)" class="mt-6 bg-white rounded-lg p-4 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Left side: Info and per page selector -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="text-sm text-gray-600">
                        <template v-if="users.meta">
                            <span class="font-medium">{{ getStartIndex() }}-{{ getEndIndex() }}</span>
                            sur <span class="font-medium">{{ users.meta.total }}</span> utilisateurs
                        </template>
                        <template v-else>
                            <span class="font-medium">{{ users.data.length }}</span> utilisateur(s) affiché(s)
                        </template>
                        <span v-if="selectedIds.length > 0" class="ml-2 text-blue-600">
                            | {{ selectedIds.length }} sélectionné(s)
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 whitespace-nowrap">Afficher:</label>
                        <select 
                            v-model.number="filters.per_page" 
                            @change="filters.page = 1; fetchUsers()" 
                            class="border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#059669] focus:border-transparent"
                        >
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="200">200</option>
                        </select>
                    </div>
                </div>

                <!-- Right side: Pagination controls -->
                <div v-if="users.meta && users.meta.last_page > 1" class="flex items-center gap-1">
                    <!-- First page -->
                    <button 
                        @click="goTo(1)" 
                        :disabled="users.meta.current_page === 1"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 disabled:hover:bg-transparent"
                        :class="users.meta.current_page === 1 ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-700'"
                        title="Première page"
                    >
                        ««
                    </button>
                    
                    <!-- Previous page -->
                    <button 
                        @click="goTo(users.meta.current_page - 1)" 
                        :disabled="users.meta.current_page === 1"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 disabled:hover:bg-transparent"
                        :class="users.meta.current_page === 1 ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-700'"
                        title="Page précédente"
                    >
                        ‹
                    </button>

                    <!-- Page numbers -->
                    <template v-for="page in getPageNumbers()" :key="page">
                        <button
                            v-if="page !== '...'"
                            @click="goTo(page)"
                            class="min-w-[2.5rem] px-3 py-1.5 text-sm font-medium rounded-md border transition-colors"
                            :class="page === users.meta.current_page 
                                ? 'bg-[#059669] text-white border-[#059669]' 
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                        >
                            {{ page }}
                        </button>
                        <span v-else class="px-2 text-gray-400">...</span>
                    </template>

                    <!-- Next page -->
                    <button 
                        @click="goTo(users.meta.current_page + 1)" 
                        :disabled="users.meta.current_page === users.meta.last_page"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 disabled:hover:bg-transparent"
                        :class="users.meta.current_page === users.meta.last_page ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-700'"
                        title="Page suivante"
                    >
                        ›
                    </button>

                    <!-- Last page -->
                    <button 
                        @click="goTo(users.meta.last_page)" 
                        :disabled="users.meta.current_page === users.meta.last_page"
                        class="px-3 py-1.5 text-sm font-medium rounded-md border transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 disabled:hover:bg-transparent"
                        :class="users.meta.current_page === users.meta.last_page ? 'border-gray-200 text-gray-400' : 'border-gray-300 text-gray-700'"
                        title="Dernière page"
                    >
                        »»
                    </button>
                </div>
                <div v-else-if="users.meta && users.meta.last_page === 1" class="text-sm text-gray-500">
                    Page 1
                </div>
            </div>
        </div>
        <div v-if="copiedMessage" class="mt-2 text-xs text-green-600">{{ copiedMessage }}</div>
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import axiosInstance from '@/axios';
import TableSection from '@/components/ui/TableSectio.vue';

const title = 'Utilisateurs';
const headers = ['Sel', 'ID', 'Nom', 'Email', 'Langue', 'Plan', 'Statut', 'Dossiers', 'Créé', 'Âge'];

const users = ref({ data: [] });
const loading = ref(false);
const selectedIds = ref([]);
const copiedMessage = ref('');

const filters = reactive({
    period: 'all',
    langue_user: '',
    search: '',
    page: 1,
    per_page: 20,
});

const fetchUsers = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        const { data } = await axiosInstance.get('/admin/users', { params });
        // Laravel pagination returns data at root level, normalize it
        users.value = {
            data: data.data || [],
            meta: {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1,
                per_page: data.per_page || 20,
                total: data.total || 0,
            },
            links: data.links || {}
        };
    } catch (e) {
        console.error('Error fetching users:', e);
        users.value = { data: [], meta: null, links: {} };
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.period = 'all';
    filters.langue_user = '';
    filters.search = '';
    filters.page = 1;
    fetchUsers();
};

watch(() => [filters.period, filters.langue_user, filters.search], () => {
    filters.page = 1;
});

const goTo = (page) => {
    if (page < 1 || page > users.value.meta?.last_page) return;
    filters.page = page;
    fetchUsers();
};

const getStartIndex = () => {
    if (!users.value.meta) return 0;
    return (users.value.meta.current_page - 1) * users.value.meta.per_page + 1;
};

const getEndIndex = () => {
    if (!users.value.meta) return 0;
    return Math.min(users.value.meta.current_page * users.value.meta.per_page, users.value.meta.total);
};

const getPageNumbers = () => {
    if (!users.value.meta) return [];
    
    const current = users.value.meta.current_page;
    const last = users.value.meta.last_page;
    const pages = [];
    
    if (last <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);
        
        if (current <= 4) {
            // Near the start
            for (let i = 2; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(last);
        } else if (current >= last - 3) {
            // Near the end
            pages.push('...');
            for (let i = last - 4; i <= last; i++) {
                pages.push(i);
            }
        } else {
            // In the middle
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(last);
        }
    }
    
    return pages;
};

const formatDate = (iso) => new Date(iso).toLocaleDateString();
const accountAge = (iso) => {
    const created = new Date(iso);
    const diff = Date.now() - created.getTime();
    return Math.floor(diff / (1000 * 60 * 60 * 24));
};

const formatStatus = (status) => {
    if (!status) return 'Inconnu';
    const map = {
        active: 'Actif',
        canceled: 'Annule',
        past_due: 'En retard',
        pending_payment: 'En attente',
    };
    return map[status] || status.replace(/_/g, ' ');
};

const statusClasses = (status) => {
    const map = {
        active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        canceled: 'border-rose-200 bg-rose-50 text-rose-700',
        past_due: 'border-amber-200 bg-amber-50 text-amber-700',
        pending_payment: 'border-indigo-200 bg-indigo-50 text-indigo-700',
    };
    return map[status] || 'border-gray-200 bg-gray-50 text-gray-600';
};

const statusDotClasses = (status) => {
    const map = {
        active: 'bg-emerald-500',
        canceled: 'bg-rose-500',
        past_due: 'bg-amber-500',
        pending_payment: 'bg-indigo-500',
    };
    return map[status] || 'bg-gray-400';
};


const selectedUsers = () => users.value.data.filter(u => selectedIds.value.includes(u.id));

const selectAllPage = () => {
    if (users.value && users.value.data) {
        selectedIds.value = users.value.data.map(u => u.id);
    }
};


const copy = (text, label) => {
    navigator.clipboard.writeText(text).then(() => {
        copiedMessage.value = label + ' copié(s)';
        setTimeout(() => copiedMessage.value = '', 2000);
    });
};

const copyEmails = () => {
    const text = selectedUsers().map(u => u.email).join('\n');
    copy(text, 'Emails');
};
const copyNames = () => {
    const text = selectedUsers().map(u => u.name).join('\n');
    copy(text, 'Noms');
};
const copyNameEmail = () => {
    const text = selectedUsers().map(u => `${u.name} <${u.email}>`).join('\n');
    copy(text, 'Nom+Email');
};
const copyCSV = () => {
    const header = 'id,name,email,langue_user,subscription_plan,subscription_status,cumulative_dossier_count,created_at';
    const rows = selectedUsers().map(u => [
        u.id,
        escapeCsv(u.name),
        u.email,
        u.langue_user,
        u.subscription_plan,
        u.subscription_status,
        u.cumulative_dossier_count,
        u.created_at
    ].join(','));
    copy([header, ...rows].join('\n'), 'CSV');
};
const escapeCsv = (val) => '"' + String(val).replace(/"/g, '""') + '"';

fetchUsers();
</script>

<style scoped>
.dashboard { padding: 1.5rem; }
table { width:100%; border-collapse: collapse; }
tbody tr:hover { background:#f8fafc; }
td, th { white-space: nowrap; }
th { background:#f8f9fa; padding:.75rem; font-weight:600; font-size:.8rem; color:#64748b; text-align:left; }
td { padding:.75rem; border-bottom:1px solid #e2e8f0; font-size:.8rem; color:#475569; }
</style>