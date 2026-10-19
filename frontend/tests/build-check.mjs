import { spawnSync } from 'node:child_process';
import { readFileSync, existsSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const frontendDirectory = resolve(fileURLToPath(new URL('..', import.meta.url)));
const projectDirectory = resolve(frontendDirectory, '..');
const result = spawnSync(process.execPath, ['node_modules/vite/bin/vite.js', 'build'], {
    cwd: frontendDirectory,
    encoding: 'utf8',
});
if (result.status !== 0) {
    process.stderr.write(result.stdout || '');
    process.stderr.write(result.stderr || '');
    throw new Error('Vite production build failed.');
}
process.stdout.write(result.stdout);

const buildDirectory = join(projectDirectory, 'public/assets/build');
const manifestPath = join(buildDirectory, '.vite/manifest.json');
if (!existsSync(manifestPath)) throw new Error('Vite build did not emit public/assets/build/.vite/manifest.json.');
const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
const entry = manifest['src/spa/main.js'];
if (!entry || typeof entry.file !== 'string' || !Array.isArray(entry.css) || entry.css.length === 0) {
    throw new Error('Vite manifest is missing the SPA entry or its compiled stylesheet.');
}
for (const file of [entry.file, ...entry.css]) {
    if (!existsSync(join(buildDirectory, file))) throw new Error(`Manifest points to a missing build asset: ${file}`);
}
console.log('OK: production manifest references existing JavaScript and CSS assets');

const loaderSource = readFileSync(join(projectDirectory, 'src/Infrastructure/View/ViteAssetLoader.php'), 'utf8');
if (!loaderSource.includes('Vite manifest is missing') || !loaderSource.includes('npm --prefix frontend run build')) {
    throw new Error('Production loader must give an actionable error when the manifest is missing.');
}
console.log('OK: production loader source reports the missing manifest and the build command');
