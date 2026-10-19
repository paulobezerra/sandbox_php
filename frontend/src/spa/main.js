import { createApp } from 'vue';
import App from './App.vue';
import router from './router.js';
import '../style.css';

const root = document.getElementById('app');
const locale = root.dataset.locale;
const bootTranslations = JSON.parse(document.getElementById('initial-translations').textContent);
window.appLocale = locale;

try {
    const response = await fetch(`/assets/i18n/${encodeURIComponent(locale)}.json`, { credentials: 'same-origin' });
    if (!response.ok) throw new Error('Catalogue unavailable');
    const translations = await response.json();
    if (typeof translations !== 'object' || translations === null || Object.values(translations).some((value) => typeof value !== 'string')) {
        throw new Error('Invalid catalogue');
    }
    window.appTranslations = translations;
} catch {
    root.replaceChildren(document.createTextNode(bootTranslations['shell.loadError']));
    root.setAttribute('aria-busy', 'false');
    throw new Error('The selected language catalogue could not be loaded.');
}

const app = createApp(App);
app.config.globalProperties.$t = (key) => window.appTranslations[key] ?? key;
app.use(router);
await router.isReady();
root.replaceChildren();
root.setAttribute('aria-busy', 'false');
app.mount(root);
