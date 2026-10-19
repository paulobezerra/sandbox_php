<script setup>
import { cn } from 'cn';

const props = defineProps({
    as: { type: String, default: 'div' },
    class: { type: [String, Array, Object], required: false },
});
</script>

<template>
    <component :is="as" data-slot="card" :class="cn('bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm', props.class)">
        <slot />
    </component>
</template>
