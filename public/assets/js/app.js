import router from './router.js';
import {api} from './services/api.js';

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

app.use(router);
app.mount('#app');
