import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@radix-icons/vue': fileURLToPath(
                new URL('./tests/stubs/radix-icons-vue.js', import.meta.url),
            ),
        },
    },
    test: {
        include: ['tests/**/*.test.js'],
        environment: 'happy-dom',
        testTimeout: 5_000,
        hookTimeout: 5_000,
        coverage: {
            provider: 'v8',
            include: ['src/**/*.{js,vue}'],
            reporter: ['text', 'json-summary'],
            thresholds: {
                lines: 95,
                branches: 85,
            },
        },
    },
});
