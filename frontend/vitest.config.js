import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    test: {
        include: ['tests/**/*.test.js'],
        environment: 'happy-dom',
        testTimeout: 5_000,
        hookTimeout: 5_000,
    },
});
