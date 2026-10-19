import { defineConfig } from 'vitest/config';

export default defineConfig({
    define: {
        'import.meta.env.VITE_TEST_APP_URL': JSON.stringify(process.env.APP_URL || 'http://localhost:8080'),
    },
    test: {
        include: ['tests/**/*.test.js'],
        testTimeout: 30_000,
        hookTimeout: 30_000,
    },
});
