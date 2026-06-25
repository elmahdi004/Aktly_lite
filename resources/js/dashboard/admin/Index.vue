<template>
    <div class="space-y-6">
        <!-- header -->
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">
                {{ isNl ? 'Beheerdersoverzicht' : 'Tableau de bord' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ isNl ? 'Realtime overzicht van dossiers, gebruikers en aanvragen.' : 'Vue d’ensemble en temps réel des dossiers, utilisateurs et demandes.' }}
            </p>
        </div>

        <!-- stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div v-for="s in statCards" :key="s.key" class="glass-card flex items-center gap-4 p-5">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl" :class="s.tile">
                    <component :is="s.icon" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ s.label }}</p>
                    <p class="font-display text-2xl font-extrabold text-slate-900">{{ s.value }}</p>
                </div>
            </div>
        </div>

        <!-- recent demandes -->
        <section class="glass-card overflow-hidden p-0">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-display text-base font-bold text-slate-800">{{ isNl ? 'Recente dossiers' : 'Dernières demandes' }}</h2>
                <router-link :to="{ name: 'adminServices' }" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    {{ isNl ? 'Alles bekijken' : 'Tout voir' }} <ArrowUpRight class="h-4 w-4" />
                </router-link>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Onderneming' : 'Entreprise' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Taal' : 'Langue' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Klant' : 'Client' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Status' : 'Statut' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id" class="border-t border-slate-100 transition hover:bg-emerald-50/40">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ r.enterprise_number }}</td>
                            <td class="px-5 py-3 uppercase text-slate-500">{{ r.langue || r.langue_entreprise || '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ r.user?.name || '—' }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="badge(r.status)">{{ statusText(r.status) }}</span></td>
                        </tr>
                        <tr v-if="!requests.length">
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">{{ isNl ? 'Geen dossiers.' : 'Aucune demande.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- recent tickets -->
        <section class="glass-card overflow-hidden p-0">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-display text-base font-bold text-slate-800">{{ isNl ? 'Recente meldingen' : 'Dernières réclamations' }}</h2>
                <router-link :to="{ name: 'adminSupport' }" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                    {{ isNl ? 'Alles bekijken' : 'Tout voir' }} <ArrowUpRight class="h-4 w-4" />
                </router-link>
            </header>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Klant' : 'Client' }}</th>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Status' : 'Statut' }}</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in messages" :key="m.id" class="border-t border-slate-100 transition hover:bg-emerald-50/40">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ m.type }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ m.demande?.user?.name || '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ m.email }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="badge(m.status)">{{ statusText(m.status) }}</span></td>
                            <td class="px-5 py-3 text-slate-500">{{ formatDate(m.created_at) }}</td>
                        </tr>
                        <tr v-if="!messages.length">
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-400">{{ isNl ? 'Geen meldingen.' : 'Aucune réclamation.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import axiosInstance from "@/axios";
import { FileText, Users, MessageSquare, ArrowUpRight } from "lucide-vue-next";

const { locale } = useI18n();
const isNl = computed(() => locale.value === "nl");

const count = ref({ demandes: 0, users: 0, tickets: 0 });
const requests = ref([]);
const messages = ref([]);

const statCards = computed(() => [
    { key: "d", label: isNl.value ? "Dossiers" : "Demandes", value: count.value.demandes, icon: FileText, tile: "bg-emerald-100 text-emerald-600" },
    { key: "u", label: isNl.value ? "Gebruikers" : "Utilisateurs", value: count.value.users, icon: Users, tile: "bg-sky-100 text-sky-600" },
    { key: "t", label: isNl.value ? "Meldingen" : "Réclamations", value: count.value.tickets, icon: MessageSquare, tile: "bg-amber-100 text-amber-600" },
]);

const badge = (s) => {
    const v = (s || "").toLowerCase();
    if (["resolved", "validated", "valide", "validé", "approved"].includes(v)) return "bg-emerald-100 text-emerald-700";
    if (["cancelled", "canceled", "refused", "rejected"].includes(v)) return "bg-rose-100 text-rose-700";
    if (["pending", "en_attente", "submitted"].includes(v)) return "bg-amber-100 text-amber-700";
    return "bg-slate-100 text-slate-600";
};
const statusText = (s) => {
    const v = (s || "").toLowerCase();
    const fr = { resolved: "Validé", validated: "Validé", cancelled: "Refusé", canceled: "Refusé", pending: "En attente" };
    const nl = { resolved: "Goedgekeurd", validated: "Goedgekeurd", cancelled: "Geweigerd", canceled: "Geweigerd", pending: "In behandeling" };
    return (isNl.value ? nl : fr)[v] || s || "—";
};
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(isNl.value ? "nl-BE" : "fr-BE") : "—");

onMounted(() => {
    getCount();
    getLastDemandes();
    getLastTickets();
});
const getCount = async () => {
    try {
        const { data } = await axiosInstance.get("count");
        count.value = { demandes: data.demandes || 0, users: data.users || 0, tickets: data.tickets || 0 };
    } catch (e) { console.error(e); }
};
const getLastDemandes = async () => {
    try { const { data } = await axiosInstance.get("last-demandes"); requests.value = Array.isArray(data) ? data : []; } catch (e) { console.error(e); }
};
const getLastTickets = async () => {
    try { const { data } = await axiosInstance.get("last-tickets"); messages.value = Array.isArray(data) ? data : []; } catch (e) { console.error(e); }
};
</script>
