<template>
<div class="service min-h-40 col-span-12 sm:col-span-6 lg:col-span-4 border p-2 rounded-lg cursor-pointer hover:shadow-sm hover:shadow-blue-500 hover:translate-y-2 hover:scale-105 duration-200">
    <input type="checkbox" class="radio peer" :name="name" id=""  @change="updateValue" :checked="modelValue">
    <div class="flex flex-col items-center gap-y-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
        <img :src="icon" alt="service_icon" />
        <h1 class="title text-xl font-bold text-center">{{ title }}</h1>
    </div>
</div>
</template>
<script setup>
import { defineProps,defineEmits } from 'vue';
const props = defineProps(['title','icon','name','modelValue']);
const emit = defineEmits(['update:modelValue']);
const updateValue = (event) => {
  emit('update:modelValue', event.target.checked);
};
</script>
<style scoped>
    .title{
        color: rgba(32, 36, 80, 1);
    }
    input{
        z-index: 100 !important;
        cursor: pointer !important;
    }
        .service{
            position:relative;
            /* min-width: 14rem !important; */
            justify-content: space-between;
        }
        .service > .radio{
            inset: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
        }
        .service:has(input:checked) {
        border:2px solid #8379c7;
        background-color: #ecf3fe;
        }
        /* .choix > .radio:checked ~ img{
            opacity: 1;
        }
        .choix > .radio:checked ~ .title > i{
            color:#4b44e5;
        } */
</style>