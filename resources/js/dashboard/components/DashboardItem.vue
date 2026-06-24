<template>
    <router-link
        :to="{ name: pathName }"
        @click="handleClick"
        :class="[
            'group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
            'focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white',
        ]"
        active-class="is-active"
    >
        <!-- left emerald rail (shown on active) -->
        <span
            aria-hidden="true"
            class="rail absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-emerald-600 opacity-0 transition-opacity duration-200"
        ></span>

        <!-- icon slot -->
        <span class="grid h-5 w-5 shrink-0 place-items-center text-slate-400 transition-colors duration-200 group-hover:text-emerald-600">
            <slot />
        </span>

        <span class="truncate">{{ title }}</span>
    </router-link>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps(["title", "icon", "pathName"]);

const handleClick = () => {
    if (props.pathName === "step1") {
        sessionStorage.removeItem("demande_id");
    }
};
</script>

<style scoped>
/* default + hover (light Aurora Glass) */
.group {
    color: rgb(71 85 105); /* slate-600 */
}
.group:hover {
    background-color: rgb(236 253 245 / 0.6); /* emerald-50/60 */
    color: rgb(4 120 87); /* emerald-700 */
}

/* active state: emerald-50 bg + emerald-700 text + left rail */
.is-active {
    background-color: rgb(236 253 245); /* emerald-50 */
    color: rgb(4 120 87); /* emerald-700 */
    font-weight: 600;
}
.is-active .rail {
    opacity: 1;
}
.is-active :deep(svg),
.is-active .grid {
    color: rgb(4 120 87); /* emerald-700 icon */
}
</style>
