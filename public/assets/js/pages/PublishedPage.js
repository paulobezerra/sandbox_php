import { api } from '../services/api.js';

export default {
    data: () => ({ page: null, loading: true, error: '' }),
    async mounted() {
        try { this.page = (await api.get(`/api/pages/${encodeURIComponent(this.$route.params.slug)}`)).page; }
        catch (error) { this.error = error.message; }
        finally { this.loading = false; }
    },
    template: `<article><p v-if="loading" class="loading">Carregando…</p><p v-else-if="error" class="alert alert-error">{{ error }}</p><template v-else-if="page"><h1>{{ page.title }}</h1><div class="markdown-content" v-html="page.html"></div></template></article>`
};
