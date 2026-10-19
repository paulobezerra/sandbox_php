<script setup>
import { cn } from 'cn';

const props = defineProps({
    variant: { type: String, default: 'default' },
    class: { type: [String, Array, Object], required: false },
});
</script>

<template>
    <div
        data-slot="alert"
        :class="cn('relative w-full rounded-lg border px-4 py-3 text-sm', variant === 'destructive' ? 'border-destructive/50 bg-destructive/5 text-destructive' : 'border-border bg-card text-card-foreground', props.class)"
    >
        <slot />
    </div>
</template>
