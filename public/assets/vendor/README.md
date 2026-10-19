# Frontend asset delivery

Vue, Vue Router, Tailwind CSS, shadcn-vue components, and Lucide are managed by the npm package under `frontend/` and bundled by Vite. Do not add or update global Vue distributions in this directory.

During development, run the PHP application and `npm --prefix frontend run dev`; PHP continues to serve the page and API. For production, run `npm --prefix frontend run build` before building the PHP image. Vite writes the manifest and compiled assets to `public/assets/build/`, which PHP/Apache serves without Node.js at runtime.
