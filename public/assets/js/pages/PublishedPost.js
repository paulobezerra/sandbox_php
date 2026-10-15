import { api } from '../services/api.js';

export default {
    data: () => ({ post: null, loading: true, error: '' }),
    async mounted() {
        try { this.post = (await api.get(`/api/posts/${encodeURIComponent(this.$route.params.slug)}`)).post; }
        catch (error) { this.error = error.message; }
        finally { this.loading = false; }
    },
    template: `<article><p v-if="loading" class="loading">Carregando…</p><p v-else-if="error" class="alert alert-error">{{ error }}</p><template v-else-if="post"><router-link to="/blog">← Blog</router-link><h1>{{ post.title }}</h1><p v-if="post.excerpt" class="lead">{{ post.excerpt }}</p><div class="markdown-content" v-html="post.html"></div></template></article>`
};
