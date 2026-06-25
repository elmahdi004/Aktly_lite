<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">
                {{ isNl ? 'Meldingen' : 'Réclamations' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ isNl ? 'Supportverzoeken en meldingen van klanten.' : 'Demandes de support et réclamations des clients.' }}
            </p>
        </div>

        <section class="glass-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Type</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Klant' : 'Client' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Dossier' : 'Demande' }}</th>
                            <th class="px-5 py-3 font-semibold">Email</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Status' : 'Statut' }}</th>
                            <th class="px-5 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id" class="border-t border-slate-100 transition hover:bg-emerald-50/40">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ r.type }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ r.demande?.user?.name || '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-400">{{ shortId(r.demande_id) }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ r.email }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="badge(r.status)">{{ statusText(r.status) }}</span></td>
                            <td class="px-5 py-3 text-slate-500">{{ formatDate(r.created_at) }}</td>
                        </tr>
                        <tr v-if="!requests.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">{{ isNl ? 'Geen meldingen.' : 'Aucune réclamation.' }}</td>
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

const { locale } = useI18n();
const isNl = computed(() => locale.value === "nl");
const requests = ref([]);

const badge = (s) => {
    const v = (s || "").toLowerCase();
    if (["resolved", "validated", "approved", "closed"].includes(v)) return "bg-emerald-100 text-emerald-700";
    if (["cancelled", "canceled", "refused", "rejected"].includes(v)) return "bg-rose-100 text-rose-700";
    if (["pending", "open", "en_attente"].includes(v)) return "bg-amber-100 text-amber-700";
    return "bg-slate-100 text-slate-600";
};
const statusText = (s) => {
    const v = (s || "").toLowerCase();
    const fr = { resolved: "Résolu", cancelled: "Annulé", canceled: "Annulé", pending: "En attente", open: "Ouvert" };
    const nl = { resolved: "Opgelost", cancelled: "Geannuleerd", canceled: "Geannuleerd", pending: "In behandeling", open: "Open" };
    return (isNl.value ? nl : fr)[v] || s || "—";
};
const shortId = (id) => (id ? String(id).slice(0, 8) : "—");
const formatDate = (d) => (d ? new Date(d).toLocaleDateString(isNl.value ? "nl-BE" : "fr-BE") : "—");

onMounted(getTickets);
async function getTickets() {
    try { const { data } = await axiosInstance.get("tickets"); requests.value = Array.isArray(data) ? data : []; }
    catch (e) { console.error(e); }
}
</script>
