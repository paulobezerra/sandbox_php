<script>
import { api } from '../../api/api.js';
import { Alert } from '../components/ui/alert/index.js';
import { Button } from '../components/ui/button/index.js';
import { Card } from '../components/ui/card/index.js';

export default {
    components: { Alert, Button, Card },
    data: () => ({ posts: [], loading: true, error: '', page: 1, total: 0, perPage: 10 }),
    computed: { pages() { return Math.max(1, Math.ceil(this.total / this.perPage)); } },
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
    }
};
</script>

<template>
    <section class="mx-auto w-full max-w-5xl space-y-6">
        <h1 class="text-3xl font-bold tracking-tight">{{ $t('post.blogTitle') }}</h1>
        <p v-if="loading" class="py-8 text-center text-sm text-muted-foreground" role="status">{{ $t('post.loading') }}</p>
        <Alert v-else-if="error" variant="destructive" role="alert">{{ error }}</Alert>
        <p v-else-if="!posts.length" class="rounded-lg border border-dashed px-6 py-10 text-center text-muted-foreground">{{ $t('post.emptyPublished') }}</p>
        <template v-else>
            <Card v-for="post in posts" :key="post.id" as="article" class="space-y-3 p-6">
                <h2 class="text-xl font-semibold"><router-link class="text-primary underline-offset-4 hover:underline" :to="'/blog/' + post.slug">{{ post.title }}</router-link></h2>
                <p>{{ post.excerpt || post.content.slice(0, 180) }}</p>
                <small class="text-sm text-muted-foreground">{{ date(post.publishedAt) }}</small>
            </Card>
            <nav v-if="pages > 1" class="flex items-center justify-between gap-4" :aria-label="$t('post.blogTitle')">
                <Button variant="outline" :disabled="page <= 1" @click="load(page - 1)">{{ $t('post.previous') }}</Button>
                <span class="text-sm text-muted-foreground">{{ $t('post.pageLabel').replace('{page}', page).replace('{pages}', pages) }}</span>
                <Button variant="outline" :disabled="page >= pages" @click="load(page + 1)">{{ $t('post.next') }}</Button>
            </nav>
        </template>
    </section>
</template>
