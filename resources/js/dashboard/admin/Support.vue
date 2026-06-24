<!-- Dashboard.vue -->
<template>
    <div class="dashboard">
        <!-- Requests Table -->
        <TableSection
            v-if="requests && requests.length > 0"
            title="Les réclamations"
            :headers="tableHeaders"
        >
            <tr v-for="request in requests" :key="request.id">
                <td>{{ request.id }}</td>
                <td>{{ request.type }}</td>
                <td>{{ request.demande.user.name }}</td>
                <td>{{ request.demande_id }}</td>
                <td>{{ request.email }}</td>
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
                <td>{{ formatDateTime(request.created_at) }}</td>
            </tr>
        </TableSection>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import TableSection from "@/components/ui/TableSectio.vue";
import axiosInstance from "@/axios";

const tableHeaders = [
    "Id",
    "Type",
    "client name",
    "demande id",
    "email",
    "status",
    "date",
];

const requests = ref([]);
onMounted(() => {
    getTickets();
});
const getTickets = async () => {
    try {
        const response = await axiosInstance.get("tickets");
        requests.value = response.data;
    } catch (error) {
        console.error(error);
    }
};
const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return date.toISOString().replace("T", " ").split(".")[0];
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
</style>
