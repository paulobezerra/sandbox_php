<script setup>
import { inject } from 'vue';

const sidebar = inject('sidebar');
</script>

<template>
    <aside
        id="application-sidebar"
        class="invisible fixed inset-y-16 left-0 z-40 h-[calc(100vh-4rem)] w-64 -translate-x-full overflow-y-auto border-r border-sidebar-border bg-sidebar p-4 text-sidebar-foreground transition-[transform,visibility] duration-200 ease-out max-[640px]:static max-[640px]:inset-auto max-[640px]:h-auto max-[640px]:max-h-[70vh] max-[640px]:w-full max-[640px]:translate-x-0"
        :data-state="sidebar.open.value ? 'open' : 'closed'"
        :aria-hidden="!sidebar.open.value"
        :inert="!sidebar.open.value"
        :class="sidebar.open.value ? 'visible translate-x-0' : 'max-[640px]:hidden'"
    >
        <slot />
    </aside>
</template>
