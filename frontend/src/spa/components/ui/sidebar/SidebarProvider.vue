<script setup>
import { computed, provide, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: undefined },
    defaultOpen: { type: Boolean, default: false }
});
const emit = defineEmits(['update:open']);
const internalOpen = ref(props.defaultOpen);
const open = computed(() => props.open ?? internalOpen.value);

watch(() => props.open, (value) => {
    if (value !== undefined) internalOpen.value = value;
});

function setOpen(value) {
    internalOpen.value = value;
    emit('update:open', value);
}

provide('sidebar', {
    open,
    setOpen,
    close: () => setOpen(false),
    toggle: () => setOpen(!open.value)
});
</script>

<template>
    <div class="flex min-h-screen w-full flex-col" :data-state="open ? 'open' : 'closed'">
        <slot />
    </div>
</template>
