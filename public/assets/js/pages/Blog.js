import { api } from '../services/api.js';

export default {
    data: () => ({ posts: [], loading: true, error: '', page: 1, total: 0, perPage: 10 }),
    computed: {
        pages() { return Math.max(1, Math.ceil(this.total / this.perPage)); }
    },
    async mounted() { await this.load(); },
    methods: {
        async load(page = this.page) {
            this.loading = true;
            this.error = '';
            try {
                const result = await api.get(`/api/posts?page=${page}&perPage=${this.perPage}`);
                this.posts = result.items;
                this.page = result.page;
                this.total = result.total;
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        date(value) { return new Date(value).toLocaleDateString(window.appLocale); }
    },
    template: `<section><h1>{{ $t('post.blogTitle') }}</h1><p v-if="loading" class="loading">{{ $t('post.loading') }}</p><p v-else-if="error" class="alert alert-error" role="alert">{{ error }}</p><p v-else-if="!posts.length" class="empty-state">{{ $t('post.emptyPublished') }}</p><template v-else><article v-for="post in posts" :key="post.id" class="content-card"><h2><router-link :to="'/blog/' + post.slug">{{ post.title }}</router-link></h2><p>{{ post.excerpt || post.content.slice(0, 180) }}</p><small>{{ date(post.publishedAt) }}</small></article><nav v-if="pages > 1" class="pagination" :aria-label="$t('post.blogTitle')"><button class="btn-secondary" :disabled="page <= 1" @click="load(page - 1)">{{ $t('post.previous') }}</button><span>{{ $t('post.pageLabel').replace('{page}', page).replace('{pages}', pages) }}</span><button class="btn-secondary" :disabled="page >= pages" @click="load(page + 1)">{{ $t('post.next') }}</button></nav></template></section>`
};
