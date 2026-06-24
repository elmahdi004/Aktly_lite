<!-- Dashboard.vue -->
<template>
    <div class="dashboard">
        <!-- Requests Table -->
        <TableSection
            v-if="requests && requests.length > 0"
            title="Les demandes"
            :headers="tableHeaders"
        >
            <tr v-for="request in requests" :key="request">
                <td>{{ request.id }}</td>
                <td>{{ request.enterprise_number }}</td>
                <td>{{ request.langue }}</td>
                <td>
                    <span
                        :class="[
                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                            {
                                'text-red-800 bg-red-100':
                                    request.status === 'cancelled',
                            },
                            {
                                'text-green-800 bg-green-100':
                                    request.status === 'resolved',
                            },
                            {
                                'text-yellow-800 bg-yellow-100':
                                    request.status === 'pending',
                            },
                        ]"
                    >
                        {{ request.status }}
                    </span>
                </td>
                <td>{{ request.user.name }}</td>
                <td>{{ request.address_service ? "X" : "O" }}</td>
                <td>{{ request.dirigeants_service ? "X" : "O" }}</td>
                <td>{{ request.cession_parts_service ? "X" : "O" }}</td>
                <td>
                    <button
                        class="btn btn-valider"
                        @click="ChangeTheStateOfRequest(request, true)"
                    >
                        Valider
                    </button>
                </td>
                <td>
                    <button
                        class="btn btn-decline"
                        @click="ChangeTheStateOfRequest(request, false)"
                    >
                        Refuser
                    </button>
                </td>
                <td>
                    <button
                        class="btn btn-modifier"
                        @click="updateRequest(request)"
                    >
                        Modifer
                    </button>
                </td>
            </tr>
        </TableSection>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import TableSection from "@/components/ui/TableSectio.vue";
import axiosInstance from "@/axios.js";
import { useRouter } from "vue-router";
const tableHeaders = [
    "Id",
    "Enterprise number",
    "Langue",
    "Status",
    "Client name",
    "Adresse service",
    "Dirigeants Service",
    "Cession se part service",
    "Valider",
    "Refuser",
    "Modifier",
];

const requests = ref([]);
const router = useRouter();
onMounted(() => {
    getDemandes();
});
const getDemandes = async () => {
    try {
        const response = await axiosInstance.get("demandes");
        requests.value = response.data;
        // console.log(response.data);
    } catch (error) {
        console.error(error);
    }
};

// valider ou refuser une demande

const ChangeTheStateOfRequest = (demande, valider) => {
    axiosInstance
        .post(`demandes/change_state/${demande.id}`, { state: valider })
        .then(({ data }) => {
            if (valider) {
                requests.value.find((ele) => ele.id == demande.id).status =
                    "resolved";
            } else {
                requests.value.find((ele) => ele.id == demande.id).status =
                    "canceled";
            }
        })
        .catch((error) => console.log(error));
};

// function for update request

const updateRequest = (request) => {
    router.push({ name: "json" });
};
</script>

<style scoped>
.dashboard {
    padding: 2rem;
    /* background-color: #f8f9fa; */
    min-height: 100vh;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    color: white;
    font-size: 1.5rem;
}

.stat-info h3 {
    margin: 0;
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
}

.table-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    /* box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); */
}

.section-title {
    color: #1e293b;
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background-color: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #64748b;
}

td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.validé {
    background-color: #dcfce7;
    color: #166534;
}

.non-validé {
    background-color: #fee2e2;
    color: #991b1b;
}

@media (max-width: 768px) {
    .dashboard {
        padding: 1rem;
    }

    .stats-container {
        grid-template-columns: 1fr;
    }
}

.btn {
    padding: 7px 16px;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    /* position: relative;
    z-index:0; */
    overflow: hidden;
}

.btn-valider {
    background: linear-gradient(135deg, #4ade80, #22c55e);
    color: white;
}

.btn-valider:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(34, 197, 94, 0.4);
}

.btn-decline {
    background: linear-gradient(135deg, #f87171, #ef4444);
    color: white;
}

.btn-decline:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(239, 68, 68, 0.4);
}
.btn-modifier {
    background: linear-gradient(
        135deg,
        #60a5fa,
        #3b82f6
    ); /* bleu clair à bleu foncé */
    color: white;
}

.btn-modifier:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(59, 130, 246, 0.4);
}
</style>
