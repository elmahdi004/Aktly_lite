<template>
    <div class="table-section shadow-lg">
        <h2 class="section-title">{{ title }}</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th v-for="header in headers" :key="header">
                            {{ header }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <slot />
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>
import { ref, defineProps } from "vue";
const props = defineProps({
    title: String,
    headers: Array,
});
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
    /* box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); */
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
    font-size: 1rem;
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
    padding: 0.5rem;
    text-align: left;
    font-weight: 500;
    color: #64748b;
    font-size: 0.9rem;
}

td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
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
