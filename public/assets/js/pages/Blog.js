import { api } from '../services/api.js';

export default {
    data: () => ({ posts: [], loading: true, error: '' }),
    async mounted() {
        try { this.posts = (await api.get('/api/posts')).items; }
        catch (error) { this.error = error.message; }
        finally { this.loading = false; }
    },
    template: `<section><h1>Blog</h1><p v-if="loading" class="loading">Carregando posts…</p><p v-else-if="error" class="alert alert-error">{{ error }}</p><p v-else-if="!posts.length" class="empty-state">Ainda não há posts publicados.</p><article v-for="post in posts" :key="post.id" class="content-card"><h2><router-link :to="'/blog/' + post.slug">{{ post.title }}</router-link></h2><p>{{ post.excerpt || post.content.slice(0, 180) }}</p><small>{{ new Date(post.publishedAt).toLocaleDateString() }}</small></article></section>`
};
