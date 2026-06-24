<template>
    <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">
            <!-- success banner -->
            <div class="animate-fade-up mb-8 flex flex-col items-center gap-3 rounded-3xl border border-emerald-100 bg-gradient-to-b from-emerald-50/80 to-white p-8 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-600"><CheckCircle2 class="h-7 w-7" /></div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ isNl ? 'Uw dossier is klaar' : 'Votre dossier est prêt' }}</h1>
                <p class="max-w-lg text-sm text-slate-500">{{ isNl ? 'Bekijk, druk af en onderteken de documenten hieronder, en dien ze in bij het Belgisch Staatsblad.' : 'Consultez, imprimez et signez les documents ci-dessous, puis déposez-les au Moniteur belge.' }}</p>
                <button @click="printAll" class="btn-aurora mt-1"><Printer class="h-4 w-4" /> {{ isNl ? 'Volledig dossier afdrukken' : 'Imprimer le dossier complet' }}</button>
            </div>

            <!-- documents grid -->
            <section class="animate-fade-up-1">
                <h2 class="mb-4 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><FileText class="h-5 w-5 text-emerald-600" /> {{ isNl ? 'Documenten' : 'Documents' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-for="(f, i) in forms" :key="i" class="group flex flex-col rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start gap-3">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><component :is="f.icon" class="h-5 w-5" /></div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold leading-tight text-slate-800">{{ f.label }}</h3>
                                <p class="mt-0.5 text-xs text-slate-400">{{ f.hint }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button @click="view(f)" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700"><Eye class="h-4 w-4" /> {{ $t('documents.buttons.view') || 'Aperçu' }}</button>
                            <button @click="print(f)" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700"><Printer class="h-4 w-4" /> {{ isNl ? 'Afdrukken' : 'Imprimer' }}</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- identity verification -->
            <section class="animate-fade-up-2 mt-10">
                <h2 class="mb-4 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><ShieldCheck class="h-5 w-5 text-emerald-600" /> {{ isNl ? 'Identiteitsverificatie' : "Vérification d'identité" }}</h2>
                <div v-if="loading" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-6 text-sm text-slate-400">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-500/30 border-t-emerald-500"></span> {{ isNl ? 'Laden…' : 'Chargement…' }}
                </div>
                <div v-else-if="people.length" class="grid gap-4">
                    <div v-for="(p, i) in people" :key="i" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 font-semibold text-slate-500">{{ (p.name || '?').slice(0,1).toUpperCase() }}</div>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ p.name || '—' }}</p>
                                    <p v-if="p.phone" class="text-xs text-slate-400">{{ p.phone }}</p>
                                </div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="badge(p.status)">{{ statusText(p.status) }}</span>
                        </div>
                        <dl v-if="rows(p).length" class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div v-for="r in rows(p)" :key="r.label" class="flex justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                <dt class="text-slate-400">{{ r.label }}</dt>
                                <dd class="text-right font-medium text-slate-700">{{ r.value }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div v-else class="rounded-2xl border border-slate-100 bg-white p-6 text-sm text-slate-400">{{ isNl ? 'Nog geen verificatiegegevens.' : "Aucune donnée de vérification pour l'instant." }}</div>
            </section>

            <!-- moniteur payment + signing -->
            <section class="animate-fade-up-3 mt-10 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-800"><Landmark class="h-5 w-5 text-emerald-600" /> {{ isNl ? 'Betaling Staatsblad' : 'Paiement Moniteur belge' }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ isNl ? 'De publicatiekosten worden betaald aan het Belgisch Staatsblad. De betalingsgegevens staan op het document hieronder.' : 'Les frais de publication se paient au Moniteur belge. Les coordonnées de paiement figurent sur le document ci-dessous.' }}</p>
                    <button @click="view(paymentForm)" class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-emerald-300 hover:text-emerald-700"><Eye class="h-4 w-4" /> {{ isNl ? 'Betalingsvoorwaarden' : 'Conditions de paiement' }}</button>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-800"><PenLine class="h-5 w-5 text-emerald-600" /> {{ isNl ? 'Hoe ondertekenen?' : 'Comment signer ?' }}</h3>
                    <ol class="mt-2 space-y-1.5 text-sm text-slate-500">
                        <li>1. {{ isNl ? 'Druk de documenten af.' : 'Imprimez les documents.' }}</li>
                        <li>2. {{ isNl ? 'Onderteken op de daarvoor voorziene plaatsen.' : 'Signez aux endroits prévus.' }}</li>
                        <li>3. {{ isNl ? 'Dien het dossier in bij het Staatsblad met het betalingsbewijs.' : 'Déposez le dossier au Moniteur avec la preuve de paiement.' }}</li>
                    </ol>
                </div>
            </section>
        <!-- preview modal -->
        <transition name="fade">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" @click.self="showModal = false">
                <div class="flex h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <span class="font-semibold text-slate-800">{{ previewLabel }}</span>
                        <div class="flex items-center gap-2">
                            <a :href="previewUrl + '&download=1'" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-emerald-700"><Printer class="h-4 w-4" /> {{ isNl ? 'Afdrukken' : 'Imprimer' }}</a>
                            <button @click="showModal = false" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"><X class="h-4 w-4" /></button>
                        </div>
                    </div>
                    <iframe v-if="previewUrl" :src="previewUrl" title="preview" class="h-full w-full flex-1 bg-slate-100"></iframe>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import axiosInstance from "@/axios.js";
import { useI18n } from "vue-i18n";
import { FileCheck2, FileText, ScrollText, IdCard, Eye, Printer, ShieldCheck, Landmark, PenLine, CheckCircle2, LayoutDashboard, X } from "lucide-vue-next";

const { t, locale } = useI18n();
const route = useRoute();
const isNl = computed(() => locale.value === "nl");

const demandeId = route.query.demande_id || sessionStorage.getItem("demande_id");
const demande = ref(null);
const verificationData = ref(null);
const loading = ref(false);
const showModal = ref(false);
const previewUrl = ref("");
const previewLabel = ref("");

const forms = computed(() => [
    { key: "form1", path: "formulaire1", icon: FileText, label: t("documents.formList.form1") || "Formulaire I", hint: isNl.value ? "Publicatieformulier" : "Formulaire de publication" },
    { key: "form2", path: "formulaire2", icon: FileText, label: t("documents.formList.form2") || "Formulaire II", hint: isNl.value ? "Publicatieformulier" : "Formulaire de publication" },
    { key: "attestation", path: "formulaire5", icon: IdCard, label: t("documents.formList.idCertificate1") || "Attestation d'identité — Modèle 1", hint: isNl.value ? "Identiteitsattest" : "Attestation d'identité" },
    { key: "pv", path: "pv_ag", icon: ScrollText, label: t("form.pv_ag") || "Procès-verbal de l'AG", hint: isNl.value ? "Notulen van de vergadering" : "Procès-verbal d'assemblée" },
]);
const paymentForm = { key: "payment", path: "payment_conditions", icon: Landmark, label: t("form.payment_conditions") || "Conditions de paiement" };

const pdfUrl = (form, download) =>
    `${window.location.origin}/pdfs/${form.path}?demande_id=${demandeId}` + (download ? "&download=1" : "");

const view = (form) => {
    previewUrl.value = pdfUrl(form, false);
    previewLabel.value = form.label;
    showModal.value = true;
};
const print = (form) => window.open(pdfUrl(form, true), "_blank");
const printAll = () => forms.value.forEach((f, i) => setTimeout(() => window.open(pdfUrl(f, true), "_blank"), i * 400));

onMounted(async () => {
    try {
        const { data } = await axiosInstance.get(`demandes/${demandeId}`);
        demande.value = data.demande;
    } catch (e) { /* ignore */ }
    await fetchVerification();
});

const fetchVerification = async () => {
    loading.value = true;
    try {
        const { data } = await axiosInstance.get(`demandes/${demandeId}/verification`);
        verificationData.value = data;
    } catch (e) {
        verificationData.value = null;
    } finally {
        loading.value = false;
    }
};

const people = computed(() => {
    const v = verificationData.value;
    if (!v) return [];
    const list = [];
    if (v.depositaire) list.push(v.depositaire);
    (v.dirigeants || []).forEach((d) => list.push(d));
    (v.shareholders || []).forEach((s) => list.push(s));
    const seen = new Set();
    return list.filter((p) => {
        const k = p.session_id || p.name;
        if (!k || seen.has(k)) return false;
        seen.add(k);
        return true;
    });
});

const LABELS = {
    firstName: { fr: "Prénom", nl: "Voornaam" },
    lastName: { fr: "Nom", nl: "Naam" },
    birthDate: { fr: "Date de naissance", nl: "Geboortedatum" },
    birthPlace: { fr: "Lieu de naissance", nl: "Geboorteplaats" },
    nationality: { fr: "Nationalité", nl: "Nationaliteit" },
    documentNumber: { fr: "N° de document", nl: "Documentnr." },
    expiryDate: { fr: "Expiration", nl: "Vervaldatum" },
    address: { fr: "Adresse", nl: "Adres" },
};
const rows = (p) => {
    const d = p.details || {};
    return Object.keys(LABELS)
        .filter((k) => d[k])
        .map((k) => ({ label: LABELS[k][isNl.value ? "nl" : "fr"], value: d[k] }));
};

const badge = (status) => {
    switch ((status || "").toLowerCase()) {
        case "approved": case "accepted": case "success": return "bg-emerald-100 text-emerald-700";
        case "pending": return "bg-amber-100 text-amber-700";
        case "refused": case "declined": case "rejected": return "bg-rose-100 text-rose-700";
        default: return "bg-slate-100 text-slate-500";
    }
};
const statusText = (status) => {
    const s = (status || "").toLowerCase();
    if (["approved", "accepted", "success"].includes(s)) return isNl.value ? "Geverifieerd" : "Vérifié";
    if (s === "pending") return isNl.value ? "In afwachting" : "En attente";
    if (["refused", "declined", "rejected"].includes(s)) return isNl.value ? "Geweigerd" : "Refusé";
    return isNl.value ? "In afwachting" : "En attente";
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
