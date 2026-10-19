import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectDirectory = resolve(fileURLToPath(new URL('../..', import.meta.url)));
const publicDirectory = resolve(projectDirectory, 'public');
const port = Number(process.env.MOCK_APP_PORT || 8080);
const shellKeys = [
    'shell.loading', 'shell.loadError', 'shell.home', 'shell.blog', 'shell.login',
    'shell.myPosts', 'shell.logout', 'shell.footer', 'shell.title', 'shell.description',
];
const mimeTypes = {
    '.json': 'application/json; charset=utf-8',
    '.svg': 'image/svg+xml',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.webp': 'image/webp',
};

async function catalogue(locale) {
    const path = resolve(publicDirectory, 'assets/i18n', `${locale}.json`);
    const source = await readFile(path, 'utf8');
    const values = JSON.parse(source);
    return Object.fromEntries(shellKeys.map((key) => [key, values[key]]));
}

function negotiateLocale(header) {
    const preferences = (header || 'en').split(',').map((value, index) => {
        const [tag, ...parameters] = value.trim().split(';');
        const quality = Number(parameters.find((parameter) => parameter.trim().startsWith('q='))?.trim().slice(2) ?? 1);
        return { tag: tag.toLowerCase(), quality, index };
    }).sort((left, right) => right.quality - left.quality || left.index - right.index);

    for (const preference of preferences) {
        if (preference.tag === 'pt-br' || preference.tag.startsWith('pt-')) return 'pt-BR';
        if (preference.tag === 'en' || preference.tag.startsWith('en-') || preference.tag === '*') return 'en';
    }
    return 'en';
}

function escapeHtml(value) {
    return String(value).replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
}

async function renderShell(request) {
    const locale = negotiateLocale(request.headers['accept-language']);
    const translations = await catalogue(locale);
    const initialTranslations = JSON.stringify(translations)
        .replaceAll('<', '\\u003c').replaceAll('>', '\\u003e').replaceAll('&', '\\u0026');

    return `<!DOCTYPE html>
<html lang="${locale}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="${escapeHtml(translations['shell.description'])}">
    <title>${escapeHtml(translations['shell.title'])}</title>
</head>
<body>
<div id="app" v-cloak data-locale="${locale}" aria-busy="true"><p id="boot-message" role="status">${escapeHtml(translations['shell.loading'])}</p></div>
<script type="application/json" id="initial-translations">${initialTranslations}</script>
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/src/spa/main.js"></script>
</body>
</html>`;
}

const server = createServer(async (request, response) => {
    const url = new URL(request.url || '/', `http://${request.headers.host || 'localhost'}`);
    if (url.pathname.startsWith('/api/')) {
        response.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
        response.end(JSON.stringify({ error: { message: 'Unexpected API request in frontend test fixture.' } }));
        return;
    }

    if (url.pathname.startsWith('/assets/')) {
        const assetPath = resolve(publicDirectory, `.${decodeURIComponent(url.pathname)}`);
        if (!assetPath.startsWith(`${publicDirectory}${sep}`)) {
            response.writeHead(400).end();
            return;
        }
        try {
            const contents = await readFile(assetPath);
            response.writeHead(200, { 'Content-Type': mimeTypes[extname(assetPath)] || 'application/octet-stream' });
            response.end(contents);
        } catch {
            response.writeHead(404).end();
        }
        return;
    }

    try {
        response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        response.end(await renderShell(request));
    } catch (error) {
        response.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
        response.end(error instanceof Error ? error.message : 'Could not render test shell.');
    }
});

server.listen(port, '0.0.0.0', () => process.stdout.write(`Mock frontend shell listening on ${port}\n`));
