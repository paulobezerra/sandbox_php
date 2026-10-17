import router from './router.js';
import {api} from './services/api.js';

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

const app = Vue.createApp({
    data: () => ({user: null}),
    async mounted() {
        window.addEventListener('auth-changed', this.onAuthChanged);
        try {
            this.user = (await api.get('/api/auth/me')).user;
        } catch {
            this.user = null;
        }
    },
    beforeUnmount() {
        window.removeEventListener('auth-changed', this.onAuthChanged);
    },
    methods: {
        onAuthChanged(event) {
            this.user = event.detail;
        },
        async logout() {
            try {
                await api.post('/api/auth/logout');
                api.clearCsrf();
                if (this.$route.path === '/my-posts') await this.$router.push('/');
            } catch (error) {
                window.alert(error.message);
            }
        }
    }
});

app.config.globalProperties.$t = (key) => window.appTranslations[key] ?? key;
app.use(router);
document.getElementById('boot-message')?.remove();
root.setAttribute('aria-busy', 'false');
app.mount('#app');
