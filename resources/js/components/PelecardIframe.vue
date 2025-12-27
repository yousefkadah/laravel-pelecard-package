<script setup>
import { computed } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    width: {
        type: [String, Number],
        default: '100%',
    },
    height: {
        type: [String, Number],
        default: '600',
    },
    frameborder: {
        type: [String, Number],
        default: '0',
    },
    scrolling: {
        type: String,
        default: 'auto',
    },
    allowtransparency: {
        type: [Boolean, String],
        default: 'true',
    },
    title: {
        type: String,
        default: 'Pelecard Payment',
    },
});

const style = computed(() => ({
    width: typeof props.width === 'number' ? `${props.width}px` : props.width,
    height: typeof props.height === 'number' ? `${props.height}px` : props.height,
    border: 'none',
}));
</script>

<template>
    <iframe
        :src="url"
        :width="width"
        :height="height"
        :frameborder="frameborder"
        :scrolling="scrolling"
        :allowtransparency="allowtransparency"
        :title="title"
        :style="style"
    ></iframe>
</template>
