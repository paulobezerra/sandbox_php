<script setup>
import { cn } from 'cn';

const props = defineProps({
    modelValue: { type: String, required: false },
    defaultValue: { type: String, required: false },
    class: { type: [String, Array, Object], required: false },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <textarea
        :value="props.modelValue ?? props.defaultValue ?? ''"
        @input="emit('update:modelValue', $event.target.value)"
        data-slot="textarea"
        :class="cn('border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-3 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm', props.class)"
    />
</template>
