<template>
    <div
        class="w-full bg-white rounded-sm p-2 flex items-center justify-between cursor-pointer hover:scale-105 transition-transform h-[50px] px-2.5 shadow-[0_2px_5px_rgba(211,209,238,0.5)]" @click="$emit('select', id)">
        <div class="flex items-center gap-4">
            <img :src="icon" alt="" class="h-[25px] w-[25px]">
            <span class="text-[16px] font-semibold tracking-wide text-gray-700">{{ label }}</span>
        </div>
        <span class="text-[18px] font-semibold text-[#5E56E7]">
            <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                <path fill-rule="evenodd"
                    d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z"
                    clip-rule="evenodd" />
            </svg> -->
            <img src="../../images/Next.svg" alt="">
        </span>
    </div>
</template>

<script setup>
defineProps({
    icon: Function,
    label: String,
    id: Number
})
</script>

<style scoped></style>
