<template>
    <h1>Hi</h1>
    <button @click="view">Get View</button>
    <iframe v-if="viewUrl" :src="viewUrl" width="100%" height="600px"></iframe>
</template>

<script>
export default {
    data() {
        return {
            viewUrl: null,
        };
    },
    methods: {
        view() {
            this.viewUrl = "/getview"; // Directly set the Laravel route URL
        },
    },
};
</script>
