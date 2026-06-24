<template>
                <p class="text-red-600 text-sm">{{ error }}</p>
</template>
<script setup>
const props = defineProps({
    error: String
})
</script>
<style scoped>
    
</style>