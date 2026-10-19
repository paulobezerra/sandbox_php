import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it } from 'vitest';
import { chromium } from 'playwright';

const baseUrl = import.meta.env.VITE_TEST_APP_URL.replace(/\/$/, '');
const english = 'en-US';
const portuguese = 'pt-BR';
let locale = english;
let authenticated = false;
let requests = [];
let browser;
let page;
let browserErrors;

async function expectText(locator, value) {
    await locator.waitFor();
    expect(await locator.textContent()).toContain(value);
}
async function expectAttribute(locator, name, value) {
    await expect.poll(() => locator.getAttribute(name)).toBe(value);
}
async function expectVisible(locator, visible = true) {
    await expect.poll(() => locator.isVisible()).toBe(visible);
}
async function expectPresent(locator, present = true) {
    await expect.poll(() => locator.count()).toBe(present ? 1 : 0);
}
async function expectUrl(url) {
    await expect.poll(() => page.url()).toBe(url);
}

async function stubApi() {
    await page.route((url) => url.origin === baseUrl && url.pathname.startsWith('/api/'), async (route) => {
        const request = route.request();
        const url = new URL(request.url());
        const path = url.pathname;
        requests.push({ path, method: request.method(), headers: request.headers(), body: request.postData() });
        const json = (data, status = 200) => route.fulfill({
            status,
            contentType: 'application/json',
            body: JSON.stringify(data),
        });

        if (path === '/api/auth/me') {
            return json({ user: authenticated ? { id: 71, name: 'Test Author', email: 'author@example.test' } : null });
        }
        if (path === '/api/posts') return json({ items: [], page: 1, total: 0 });
        if (path === '/api/me/posts') return json({ items: [], page: 1, total: 0 });
        if (path === '/api/auth/csrf') return json({ csrfToken: 'vitest-csrf' });
        if (path === '/api/auth/login') {
            authenticated = true;
            return json({ user: { id: 71, name: 'Test Author', email: 'author@example.test' }, csrfToken: 'login-csrf' });
        }
        if (path === '/api/auth/register') {
            return json({ user: { id: 72, name: 'Test Author', email: 'author@example.test' } }, 201);
        }
        if (path === '/api/auth/logout') {
            authenticated = false;
            return json({ message: locale === 'pt-BR' ? 'Você saiu da sua conta.' : 'You have signed out.' });
        }
        if (path === '/api/me/markdown-preview') {
            const html = '<p>&lt;script&gt;alert(1)&lt;/script&gt; <a href="#">unsafe link</a></p>';
            return json({ html });
        }
        return json({ error: { message: `Unexpected test API route: ${path}` } }, 404);
    });
}

async function visit(path, { language = english, signedIn = false, width = 1280 } = {}) {
    locale = language === portuguese ? 'pt-BR' : 'en';
    authenticated = signedIn;
    requests = [];
    await page.setViewportSize({ width, height: 900 });
    await page.setExtraHTTPHeaders({ 'Accept-Language': language });
    await page.unrouteAll({ behavior: 'wait' });
    await stubApi();
    await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(100);
    if (browserErrors.length > 0) throw new Error(browserErrors[0].message);
    await page.locator('#app[data-v-app]').waitFor({ timeout: 5000 });
}

beforeAll(async () => {
    browser = await chromium.launch({
        headless: true,
        ...(process.env.CHROME_BIN ? { executablePath: process.env.CHROME_BIN } : { channel: 'chrome' }),
    });
});

beforeEach(async () => {
    page = await browser.newPage();
    browserErrors = [];
    page.on('pageerror', (error) => browserErrors.push(error));
    locale = 'en';
    authenticated = false;
    requests = [];
});

afterEach(async () => {
    await page.close();
});

afterAll(async () => {
    await browser?.close();
});

describe('SPA browser integration with mocked API', () => {
    it('serves the Vue shell and selects Vite development modules or built production assets', async () => {
        const response = await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });
        expect(response?.ok()).toBe(true);
        const html = await response.text();
        expect(html).toContain('id="app"');
        expect(html).toContain('id="initial-translations"');
        expect(html).toMatch(/(?:localhost:5173\/src\/spa\/main\.js|\/assets\/build\/[^" ]+\.js)/);
        expect(html).not.toContain('/assets/vendor/vue');
        await page.locator('#app[data-v-app]').waitFor();
    });

    it.each([
        ['/', english, 'A reusable PHP, Slim, and Vue foundation', 'en'],
        ['/', portuguese, 'Uma base reutilizável em PHP, Slim e Vue', 'pt-BR'],
        ['/blog', english, 'Blog', 'en'],
        ['/login', english, 'Sign in', 'en'],
        ['/register', english, 'Create account', 'en'],
        ['/my-posts', portuguese, 'Meus Posts', 'pt-BR'],
    ])('mounts %s in locale %s', async (path, language, text, htmlLang) => {
        await visit(path, { language, signedIn: path === '/my-posts' });
        await expectAttribute(page.locator('html'), 'lang', htmlLang);
        await expectText(page.locator('#app'), text);
        expect(await page.locator('#app').textContent()).not.toMatch(/Loading the application|Carregando a aplicação/);
    });

    it('keeps technology marks local, accessible, and linked to official documentation in both locales', async () => {
        for (const language of [english, portuguese]) {
            await visit('/', { language });
            for (const [asset, label, destination] of [
                ['php', 'PHP', 'https://www.php.net/'],
                ['slim', 'Slim', 'https://www.slimframework.com/'],
                ['vue', 'Vue', 'https://vuejs.org/'],
                ['mysql', 'MySQL', 'https://dev.mysql.com/doc/'],
                ['docker', 'Docker Compose', 'https://docs.docker.com/compose/'],
                ['phinx', 'Phinx', 'https://phinx.org/'],
                ['vite', 'Vite', 'https://vite.dev/'],
                ['tailwindcss', 'Tailwind CSS', 'https://tailwindcss.com/'],
                ['shadcn-vue', 'shadcn-vue', 'https://www.shadcn-vue.com/'],
                ['lucide', 'Lucide', 'https://lucide.dev/'],
            ]) {
                const mark = page.locator(`a[href="${destination}"] img[src="/assets/images/technology/${asset}.svg"]`);
                await expectAttribute(mark, 'alt', label);
                await expectAttribute(mark.locator('xpath=..'), 'href', destination);
            }
            await expectText(page.locator('#app'), 'npm --prefix frontend run test');
            await expectText(page.locator('#app'), 'public/assets/build/');
        }
    });

    it('preserves Home contents, repository guidance, installation links, and operational commands in both locales', async () => {
        for (const language of [english, portuguese]) {
            await visit('/', { language });
            for (const anchor of ['capacidades', 'stack', 'arquitetura', 'instalacao', 'primeiro-uso', 'rotas', 'qualidade', 'producao', 'customizacao', 'sdd-lite']) {
                await expectPresent(page.locator(`#${anchor}`));
                await expectPresent(page.locator(`a[href="#${anchor}"]`));
            }
            await expectPresent(page.locator('a[href="https://github.com/paulobezerra/sandbox_php"]'));
            await expectPresent(page.locator('a[href="https://github.com/paulobezerra/sandbox_php/blob/main/docs/customization.md"]'));
            await expectPresent(page.locator('a[href="https://getcomposer.org/doc/03-cli.md#create-project"]'));
            await expect.poll(() => page.locator('a[href="/docs"]').count()).toBeGreaterThan(0);
            await expectText(page.locator('#app'), 'composer openapi:generate');
            await expectText(page.locator('#app'), 'composer migrate:status');
            await expectText(page.locator('#app'), 'composer quality');
            await expectText(page.locator('#app'), 'npm --prefix frontend run dev');
        }
    });

    it('preserves the responsive sidebar controls, closed default, navigation, and anonymous links', async () => {
        for (const width of [1280, 390]) {
            await visit('/', { width });
            const sidebar = page.locator('#application-sidebar');
            const toolbar = page.locator('.shell-toolbar');
            const toggle = toolbar.locator('.sidebar-toggle');
            await expectVisible(toolbar);
            await expect.poll(() => toolbar.getByRole('link', { name: 'Sandbox PHP' }).count()).toBe(1);
            await expectVisible(toggle);
            await expectAttribute(toggle, 'aria-label', 'Open navigation menu');
            await expectAttribute(toggle, 'aria-expanded', 'false');
            await expectAttribute(sidebar, 'data-state', 'closed');
            await expectAttribute(sidebar, 'aria-hidden', 'true');
            await expect.poll(() => sidebar.evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgb(23, 32, 51)');
            await expect.poll(() => toolbar.evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgb(23, 32, 51)');
            await expectPresent(sidebar.getByRole('link', { name: 'Log in', includeHidden: true }));
            await expectPresent(sidebar.getByRole('link', { name: 'My Posts', includeHidden: true }), false);
            const main = page.locator('main');
            const toolbarBefore = await toolbar.boundingBox();
            const closedSidebar = await sidebar.boundingBox();
            if (width > 640) {
                expect(Math.round(closedSidebar.x)).toBe(-256);
            } else {
                expect(closedSidebar).toBeNull();
            }
            const viewportWidth = page.viewportSize().width;
            await toggle.click();
            await expectAttribute(sidebar, 'data-state', 'open');
            await expect.poll(async () => {
                const box = await sidebar.boundingBox();
                return box ? Math.round(box.y) : -1;
            }).toBe(64);
            if (width > 640) {
                await expect.poll(async () => {
                    const box = await main.boundingBox();
                    return box ? Math.round(box.x) : -1;
                }).toBe(256);
            }
            const toolbarAfter = await toolbar.boundingBox();
            const sidebarBox = await sidebar.boundingBox();
            const mainBox = await main.boundingBox();
            expect(Math.round(toolbarAfter.x)).toBe(0);
            expect(Math.round(toolbarAfter.y)).toBe(0);
            expect(Math.round(toolbarAfter.width)).toBe(viewportWidth);
            expect(Math.round(toolbarAfter.y + toolbarAfter.height)).toBe(Math.round(sidebarBox.y));
            expect(toolbarAfter.x).toBe(toolbarBefore.x);
            if (width > 640) {
                expect(Math.round(mainBox.x)).toBe(Math.round(sidebarBox.width));
                expect(Math.round(mainBox.y)).toBe(Math.round(toolbarAfter.y + toolbarAfter.height));
            } else {
                expect(Math.round(mainBox.x)).toBe(0);
                expect(mainBox.y).toBeGreaterThanOrEqual(sidebarBox.y + sidebarBox.height);
            }
            await expectAttribute(toggle, 'aria-label', 'Close navigation menu');
            await expectAttribute(toggle, 'aria-expanded', 'true');
            await expectPresent(sidebar.getByRole('button', { name: 'Close navigation menu' }), false);
            await expectVisible(page.getByRole('link', { name: 'Log in' }));
            await expectVisible(page.getByRole('link', { name: 'Blog' }));
            const activeLink = sidebar.getByRole('link', { name: 'Home' });
            await expect.poll(() => activeLink.evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgba(0, 0, 0, 0)');
            await expect.poll(() => activeLink.evaluate((element) => getComputedStyle(element).color)).toBe('rgb(255, 255, 255)');
            const hero = page.locator('article > header');
            await expect.poll(() => hero.evaluate((element) => getComputedStyle(element).backgroundImage)).toContain('linear-gradient');
            await expect.poll(() => page.locator('article').evaluate((element) => getComputedStyle(element).maxWidth)).toBe('1280px');
            await expect.poll(() => hero.evaluate((element) => {
                const title = element.querySelector('h1');
                const lead = element.querySelector('p.text-xl');
                return Math.round(lead.getBoundingClientRect().top - title.getBoundingClientRect().bottom);
            })).toBeLessThan(32);
            await page.evaluate(() => window.scrollTo(0, 500));
            await expect.poll(async () => Math.round((await toolbar.boundingBox()).y)).toBe(0);
            if (width > 640) {
                await expect.poll(async () => Math.round((await sidebar.boundingBox()).y)).toBe(64);
            }
            await toggle.click();
            await expectAttribute(toggle, 'aria-label', 'Open navigation menu');
            await expectAttribute(toggle, 'aria-expanded', 'false');
            await expectAttribute(sidebar, 'data-state', 'closed');
        }
    });

    it('shows authenticated navigation and simulates login/logout without creating an account', async () => {
        await visit('/my-posts', { signedIn: true });
        const sidebar = page.locator('#application-sidebar');
        await expectPresent(sidebar.getByRole('link', { name: 'My Posts', includeHidden: true }));
        await expectPresent(sidebar.getByRole('button', { name: 'Log out', includeHidden: true }));
        await expectPresent(sidebar.getByRole('link', { name: 'Log in', includeHidden: true }), false);
        await page.getByRole('button', { name: 'Open navigation menu' }).click();
        await expectVisible(page.getByRole('link', { name: 'My Posts' }));
        await page.getByRole('button', { name: 'Log out' }).click();
        await expectVisible(page.getByRole('link', { name: 'Log in' }));
        expect(requests.some(({ path }) => path === '/api/auth/logout')).toBe(true);
        expect(requests.some(({ path }) => path === '/api/auth/register')).toBe(false);

        await page.goto(`${baseUrl}/login`);
        await page.locator('#app[data-v-app]').waitFor();
        const loginCard = page.locator('[data-slot="card"]');
        expect(await loginCard.evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgb(255, 255, 255)');
        await expect.poll(() => page.locator('label[for="email"]').evaluate((element) => getComputedStyle(element).display)).toBe('block');
        await expect.poll(() => page.locator('#email').evaluate((element) => Math.round(element.getBoundingClientRect().width))).toBeGreaterThan(300);
        await expect.poll(() => page.getByRole('button', { name: 'Sign in' }).evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgb(37, 99, 235)');
        await page.locator('#email').fill('author@example.test');
        await page.locator('#password').fill('a-test-password-that-is-long');
        await page.getByRole('button', { name: 'Sign in' }).click();
        await expectUrl(`${baseUrl}/my-posts`);
        await expectPresent(page.locator('#application-sidebar').getByRole('link', { name: 'My Posts', includeHidden: true }));
        const loginRequest = requests.find(({ path }) => path === '/api/auth/login');
        expect(loginRequest?.headers['x-csrf-token']).toBe('vitest-csrf');
        expect(JSON.parse(loginRequest?.body || '{}')).toEqual({email: 'author@example.test', password: 'a-test-password-that-is-long'});
        expect(requests.every(({ path }) => path !== '/api/auth/register')).toBe(true);
    });

    it('simulates account registration, localized login, and logout without database writes', async () => {
        await visit('/register', { language: portuguese });
        await page.locator('#name').fill('José Silva');
        await page.locator('#email').fill('author@example.test');
        await page.locator('#password').fill('a-test-password-that-is-long');
        await page.getByRole('button', { name: 'Criar conta' }).click();
        await expectText(page.getByRole('status'), 'Conta criada. Agora você pode entrar.');
        const registrationRequest = requests.find(({ path }) => path === '/api/auth/register');
        expect(JSON.parse(registrationRequest?.body || '{}')).toEqual({name: 'José Silva', email: 'author@example.test', password: 'a-test-password-that-is-long'});
        await page.getByRole('link', { name: 'Voltar para entrar' }).click();
        await page.locator('#email').fill('author@example.test');
        await page.locator('#password').fill('a-test-password-that-is-long');
        await page.getByRole('button', { name: 'Entrar' }).click();
        await expectUrl(`${baseUrl}/my-posts`);
        await expectPresent(page.locator('#application-sidebar').getByRole('link', { name: 'Meus Posts', includeHidden: true }));
        await page.getByRole('button', { name: 'Abrir menu de navegação' }).click();
        await page.getByRole('button', { name: 'Sair' }).click();
        await expectVisible(page.getByRole('link', { name: 'Entrar' }));
        expect(requests.some(({ path }) => path === '/api/auth/register')).toBe(true);
        expect(requests.some(({ path }) => path === '/api/auth/login')).toBe(true);
        expect(requests.some(({ path }) => path === '/api/auth/logout')).toBe(true);
    });

    it('keeps same-origin localized API access and retries an expired CSRF token once', async () => {
        let mutationCount = 0;
        let csrfCount = 0;
        await visit('/my-posts', { language: portuguese, signedIn: true });
        await page.route('**/api/auth/csrf', async (route) => {
            csrfCount += 1;
            await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ csrfToken: `token-${csrfCount}` }) });
        });
        await page.route('**/api/me/markdown-preview', async (route) => {
            mutationCount += 1;
            expect(route.request().postDataJSON()).toEqual({content: '## preview'});
            if (mutationCount === 1) {
                await route.fulfill({ status: 419, contentType: 'application/json', body: JSON.stringify({ error: { message: 'expired' } }) });
                return;
            }
            expect(route.request().headers()['x-csrf-token']).toBe('token-2');
            await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ html: '<p>safe</p>' }) });
        });
        await page.locator('#content').fill('## preview');
        await page.getByRole('button', { name: 'Pré-visualizar' }).click();
        await expectText(page.locator('.markdown-content.preview'), 'safe');
        expect(mutationCount).toBe(2);
        expect(csrfCount).toBe(2);
        const apiRequests = requests.filter(({ path }) => path.startsWith('/api/'));
        expect(apiRequests.length).toBeGreaterThan(0);
        expect(apiRequests.every(({ headers }) => headers['accept-language'] === 'pt-BR')).toBe(true);
        expect(new URL(page.url()).origin).toBe(baseUrl);
    });

    it('keeps Markdown preview HTML inert and shows a safe loading fallback when locale data fails', async () => {
        await visit('/my-posts', { signedIn: true });
        await page.locator('#content').fill('<script>alert(1)</script>');
        await page.route('**/api/me/markdown-preview', (route) => route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ html: '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>' }),
        }));
        await page.getByRole('button', { name: 'Preview' }).click();
        const preview = page.locator('.markdown-content.preview');
        await expectText(preview, '<script>alert(1)</script>');
        await expectPresent(preview.locator('script'), false);

        await page.route('**/assets/i18n/en.json', (route) => route.abort());
        await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });
        await expectText(page.locator('#app'), 'The application could not load its language data. Please reload the page.');
        await expectPresent(page.locator('#app[data-v-app]'), false);
    });

    it('returns the SPA not-found view for removed routes and keeps the shell mounted', async () => {
        for (const path of ['/features', '/architecture', '/guide', '/about', '/admin/content', '/uma-pagina-antiga']) {
            await visit(path);
            await expectPresent(page.locator('#app[data-v-app]'));
            await expect.poll(() => page.locator('#app').textContent()).toMatch(/Page not found|Página não encontrada/i);
        }
    });
});
