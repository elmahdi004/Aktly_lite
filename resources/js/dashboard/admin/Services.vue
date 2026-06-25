<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900">
                {{ isNl ? 'Dossiers' : 'Demandes' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ isNl ? 'Valideer, weiger of bekijk de ingediende zetelverplaatsingen.' : 'Validez, refusez ou consultez les transferts de siège soumis.' }}
            </p>
        </div>

        <section class="glass-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/70 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Onderneming' : 'Entreprise' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Taal' : 'Langue' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Klant' : 'Client' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Dienst' : 'Service' }}</th>
                            <th class="px-5 py-3 font-semibold">{{ isNl ? 'Status' : 'Statut' }}</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id" class="border-t border-slate-100 transition hover:bg-emerald-50/40">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ r.enterprise_number }}</td>
                            <td class="px-5 py-3 uppercase text-slate-500">{{ r.langue || r.langue_entreprise || '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ r.user?.name || '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ isNl ? 'Zetelverplaatsing' : 'Transfert de siège' }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="badge(r.status)">{{ statusText(r.status) }}</span></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="changeState(r, true)" :title="isNl ? 'Goedkeuren' : 'Valider'"
                                        class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-100 text-emerald-600 transition hover:bg-emerald-600 hover:text-white">
                                        <Check class="h-4 w-4" />
                                    </button>
                                    <button @click="changeState(r, false)" :title="isNl ? 'Weigeren' : 'Refuser'"
                                        class="grid h-8 w-8 place-items-center rounded-lg bg-rose-100 text-rose-600 transition hover:bg-rose-600 hover:text-white">
                                        <X class="h-4 w-4" />
                                    </button>
                                    <button @click="viewDossier(r)" :title="isNl ? 'Bekijken' : 'Consulter'"
                                        class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-slate-800 hover:text-white">
                                        <Eye class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!requests.length">
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">{{ isNl ? 'Geen dossiers.' : 'Aucune demande.' }}</td>
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
import { useRouter } from "vue-router";
import axiosInstance from "@/axios.js";
import { Check, X, Eye } from "lucide-vue-next";

const { locale } = useI18n();
const isNl = computed(() => locale.value === "nl");
const router = useRouter();
const requests = ref([]);

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

onMounted(getDemandes);
async function getDemandes() {
    try { const { data } = await axiosInstance.get("demandes"); requests.value = Array.isArray(data) ? data : []; }
    catch (e) { console.error(e); }
}

const changeState = (demande, valider) => {
    axiosInstance.post(`demandes/change_state/${demande.id}`, { state: valider })
        .then(() => {
            const row = requests.value.find((e) => e.id === demande.id);
            if (row) row.status = valider ? "resolved" : "canceled";
        })
        .catch((e) => console.error(e));
};

const viewDossier = (request) => {
    router.push({ name: "form", query: { demande_id: request.id } });
};
</script>
