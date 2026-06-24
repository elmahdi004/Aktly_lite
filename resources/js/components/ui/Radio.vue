<template lang="">
<div class="choix flex items-center border-2 px-6 py-3 rounded-lg">
                <input type="radio" class="radio peer cursor-pointer"  id="" :value="value" v-model="model" :name="name">
                <div class="title flex items-center">
                    <i :class="['text-[#95a5b8]',icon]"></i>
                    <p class=" font-medium ms-2 text-[#464c52]" for="fr">{{title}}</p>
                </div>
                <img src="/public/assets/done.png" class="w-5 h-5 opacity-0" alt="done_icon" srcset="">
</div>
</template>
<script setup>
import { defineProps , computed} from 'vue';
const props = defineProps(['title', 'icon', 'value', 'name', 'modelValue']);
const emit = defineEmits(['update:modelValue']);

const model = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
});</script>
<style scoped>
    .choix{
        position:relative;
        min-width: 14rem !important;
        justify-content: space-between;
    }
    .choix > .radio{
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
    }
    .choix:has(input:checked) {
    border:2px solid #8379c7;
    background-color: #ecf3fe;
    }
    .choix > .radio:checked ~ img{
        opacity: 1;
    }
    .choix > .radio:checked ~ .title > i{
        color:#4b44e5;
    }

    @media (max-width: 768px) {
        .choix{
            min-width: 100% !important;
            width: 100%;
        }
    }
</style>