import { beforeEach, describe, expect, it, vi } from 'vitest';

const { createApp, app, router } = vi.hoisted(() => {
    const app = { config: { globalProperties: {} }, use: vi.fn(), mount: vi.fn() };
    const router = { isReady: vi.fn().mockResolvedValue(undefined) };
    return { createApp: vi.fn(() => app), app, router };
});

vi.mock('vue', () => ({ createApp }));
vi.mock('../src/spa/App.vue', () => ({ default: { name: 'App' } }));
vi.mock('../src/spa/router.js', () => ({ default: router }));

function mountBootstrap() {
    const root = document.createElement('div');
    root.id = 'app';
    root.dataset.locale = 'pt-BR';
    root.setAttribute('aria-busy', 'true');
    root.append(document.createElement('span'));
    const initial = document.createElement('script');
    initial.id = 'initial-translations';
    initial.textContent = JSON.stringify({ 'shell.loadError': 'load failed' });
    document.body.replaceChildren(root, initial);
    return root;
}

beforeEach(() => {
    vi.resetModules();
    vi.clearAllMocks();
    router.isReady.mockResolvedValue(undefined);
    window.appTranslations = undefined;
});

describe('SPA bootstrap', () => {
    it('loads the selected catalogue and mounts the localized app', async () => {
        const root = mountBootstrap();
        globalThis.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ hello: 'Olá' }),
        });

        await import('../src/spa/main.js');

        expect(fetch).toHaveBeenCalledWith('/assets/i18n/pt-BR.json', { credentials: 'same-origin' });
        expect(window.appLocale).toBe('pt-BR');
        expect(app.config.globalProperties.$t('hello')).toBe('Olá');
        expect(app.config.globalProperties.$t('missing')).toBe('missing');
        expect(app.use).toHaveBeenCalledWith(router);
        expect(app.mount).toHaveBeenCalledWith(root);
        expect(root.getAttribute('aria-busy')).toBe('false');
        expect(root.childNodes).toHaveLength(0);
    });

    it.each([
        ['unavailable', { ok: false, json: async () => ({}) }],
        ['null catalogue', { ok: true, json: async () => null }],
        ['non-string catalogue values', { ok: true, json: async () => ({ hello: 1 }) }],
    ])('shows the boot fallback for an %s catalogue', async (_label, response) => {
        const root = mountBootstrap();
        globalThis.fetch = vi.fn().mockResolvedValue(response);

        await expect(import('../src/spa/main.js')).rejects.toThrow('The selected language catalogue could not be loaded.');
        expect(root.textContent).toBe('load failed');
        expect(root.getAttribute('aria-busy')).toBe('false');
        expect(app.mount).not.toHaveBeenCalled();
    });

    it('declares the public, authenticated, and fallback routes', async () => {
        vi.doUnmock('../src/spa/router.js');
        vi.resetModules();
        const { default: frontendRouter } = await import('../src/spa/router.js');
        const routes = frontendRouter.getRoutes();
        expect(routes.map(({ path }) => path)).toEqual(expect.arrayContaining([
            '/', '/login', '/register', '/blog', '/blog/:slug', '/my-posts', '/:pathMatch(.*)*',
        ]));
        expect(routes.find(({ path }) => path === '/login').meta.authMode).toBe('login');
        expect(routes.find(({ path }) => path === '/register').meta.authMode).toBe('register');
    });
});
