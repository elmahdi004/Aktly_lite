<template>
    <div class="aurora-wrap relative min-h-full w-full">
        <!-- soft aurora accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="aurora-blob" style="width:420px;height:420px;background:#a7f3d0;top:-180px;right:-100px;opacity:.20;"></div>
            <div class="aurora-blob" style="width:360px;height:360px;background:#6ee7b7;bottom:-200px;left:-120px;opacity:.14;"></div>
        </div>

        <div class="relative z-10 mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:py-10">
            <!-- ===== heading ===== -->
            <div class="animate-fade-up mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="brand-gradient grid h-11 w-11 shrink-0 place-items-center rounded-2xl">
                        <LifeBuoy class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                            {{ $t("table.title") }}
                        </h1>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $t("table.headers.type") }} · {{ $t("table.headers.status") }} · {{ $t("table.headers.date") }}</p>
                    </div>
                </div>
                <span
                    v-if="requests && requests.length"
                    class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700"
                >
                    <Inbox class="h-4 w-4" />
                    {{ requests.length }}
                </span>
            </div>

            <!-- ===== tickets table / cards ===== -->
            <div v-if="requests && requests.length > 0" class="animate-fade-up-1">
                <!-- desktop table -->
                <div class="glass-card hidden overflow-hidden p-0 md:block">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/60 text-xs uppercase tracking-wide text-slate-500">
                                    <th
                                        v-for="(header, i) in translatedHeaders"
                                        :key="i"
                                        class="whitespace-nowrap px-5 py-3.5 font-semibold"
                                    >
                                        {{ header }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="request in requests"
                                    :key="request.id"
                                    class="transition-colors hover:bg-emerald-50/40"
                                >
                                    <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-400">#{{ request.id }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-800">{{ request.type }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ request.demande.user.name }}</td>
                                    <td class="px-5 py-4">
                                        <span class="font-mono text-xs text-slate-500" :title="request.demande_id">{{ request.demande_id }}</span>
                                    </td>
                                    <td class="max-w-[16rem] px-5 py-4 text-slate-600">
                                        <span class="block truncate" :title="request.description">
                                            {{ truncateText(request.description, 30) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ request.email }}</td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusClass(request.status)"
                                        >
                                            <component :is="statusIcon(request.status)" class="h-3.5 w-3.5" />
                                            {{ $t(`table.status.${request.status}`) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ formatDateTime(request.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- mobile cards -->
                <div class="grid gap-4 md:hidden">
                    <div
                        v-for="request in requests"
                        :key="request.id"
                        class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:border-emerald-200"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-display text-base font-bold text-slate-900">{{ request.type }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-400">#{{ request.id }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="statusClass(request.status)"
                            >
                                <component :is="statusIcon(request.status)" class="h-3.5 w-3.5" />
                                {{ $t(`table.status.${request.status}`) }}
                            </span>
                        </div>

                        <dl class="mt-4 space-y-2.5 text-sm">
                            <div class="flex items-start gap-2.5">
                                <User class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-slate-400">{{ $t("table.headers.clientName") }}</dt>
                                    <dd class="text-slate-700">{{ request.demande.user.name }}</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <Hash class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-slate-400">{{ $t("table.headers.demandeId") }}</dt>
                                    <dd class="truncate font-mono text-xs text-slate-600" :title="request.demande_id">{{ request.demande_id }}</dd>
                                </div>
                            </div>
                            <div v-if="request.description" class="flex items-start gap-2.5">
                                <AlignLeft class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-slate-400">{{ $t("table.headers.description") }}</dt>
                                    <dd class="text-slate-600" :title="request.description">{{ truncateText(request.description, 60) }}</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <Mail class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-slate-400">{{ $t("table.headers.email") }}</dt>
                                    <dd class="truncate text-slate-600">{{ request.email }}</dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <Clock class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                <div class="min-w-0">
                                    <dt class="text-xs font-medium text-slate-400">{{ $t("table.headers.date") }}</dt>
                                    <dd class="text-slate-600">{{ formatDateTime(request.created_at) }}</dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- ===== empty state ===== -->
            <div v-else class="animate-fade-up-1">
                <div class="glass-card flex flex-col items-center justify-center px-6 py-16 text-center sm:py-20">
                    <div class="grid h-16 w-16 place-items-center rounded-3xl bg-emerald-50 text-emerald-500 ring-1 ring-emerald-100">
                        <Inbox class="h-8 w-8" />
                    </div>
                    <h2 class="font-display mt-5 text-lg font-bold text-slate-900">{{ $t("table.title") }}</h2>
                    <p class="mt-1.5 max-w-sm text-sm text-slate-500">{{ $t("table.headers.status") }} · {{ $t("table.headers.date") }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import TableSection from "@/components/ui/TableSectio.vue";
import axiosInstance from '@/axios.js';
import { useI18n } from "vue-i18n";
import { LifeBuoy, Inbox, User, Hash, AlignLeft, Mail, Clock, CheckCircle2, Clock3, XCircle, Archive } from "lucide-vue-next";

const { t } = useI18n();

const tableHeaders = [
  "table.headers.id",
  "table.headers.type",
  "table.headers.clientName",
  "table.headers.demandeId",
  "table.headers.description",
  "table.headers.email",
  "table.headers.status",
  "table.headers.date",
];

const translatedHeaders = tableHeaders.map((key) => t(key));

const requests = ref([]);

onMounted(() => {
  getTickets();
});

const getTickets = async () => {
  try {
    const response = await axiosInstance.get("mytickets");
    requests.value = response.data;
  } catch (error) {
    console.error(error);
  }
};

const formatDateTime = (dateString) => {
  const date = new Date(dateString);
  return date.toISOString().replace("T", " ").split(".")[0];
};

// ✅ Fonction utilitaire pour tronquer le texte
const truncateText = (text, maxLength) => {
  if (!text) return "";
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
};

// ---- Aurora status presentation (display-only; raw status key drives the mapping) ----
const statusClass = (status) => {
  const map = {
    pending: "bg-amber-50 text-amber-700",
    accepted: "bg-emerald-50 text-emerald-700",
    resolved: "bg-slate-100 text-slate-600",
    refused: "bg-rose-50 text-rose-700",
    cancelled: "bg-rose-50 text-rose-700",
  };
  return map[status] || "bg-slate-100 text-slate-600";
};

const statusIcon = (status) => {
  const map = {
    pending: Clock3,
    accepted: CheckCircle2,
    resolved: Archive,
    refused: XCircle,
    cancelled: XCircle,
  };
  return map[status] || Clock3;
};
</script>
