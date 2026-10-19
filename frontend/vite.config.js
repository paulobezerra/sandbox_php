import { resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

const frontendDirectory = fileURLToPath(new URL('.', import.meta.url))
const projectDirectory = resolve(frontendDirectory, '..')
const phpOrigin = new URL(process.env.APP_URL ?? 'http://localhost:8080').origin

export default defineConfig(({ command }) => ({
  root: frontendDirectory,
  base: command === 'serve' ? '/' : '/assets/build/',
  plugins: [vue(), tailwindcss()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    cors: { origin: phpOrigin },
    fs: { allow: [projectDirectory] },
    hmr: { host: 'localhost' },
  },
  build: {
    outDir: resolve(projectDirectory, 'public/assets/build'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: resolve(frontendDirectory, 'src/spa/main.js'),
    },
  },
}))
