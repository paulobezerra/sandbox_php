<script>
import { api } from '../../api/api.js';
import { Alert } from '../components/ui/alert/index.js';
import { Button } from '../components/ui/button/index.js';
import { Card } from '../components/ui/card/index.js';

export default {
    components: { Alert, Button, Card },
    data: () => ({ post: null, loading: true, error: '' }),
    async mounted() {
        try { this.post = (await api.get(`/api/posts/${encodeURIComponent(this.$route.params.slug)}`)).post; }
        catch (error) { this.error = error.message; }
        finally { this.loading = false; }
    }
};
</script>

<template>
    <article class="mx-auto w-full max-w-5xl space-y-6">
        <p v-if="loading" class="py-8 text-center text-sm text-muted-foreground" role="status">{{ $t('post.loading') }}</p>
        <Alert v-else-if="error" variant="destructive" role="alert">{{ error }}</Alert>
        <template v-else-if="post">
            <Button as-child variant="link" class="h-auto px-0"><router-link to="/blog">{{ $t('post.backToBlog') }}</router-link></Button>
            <Card as="section" class="space-y-4 p-6 sm:p-8">
                <h1 class="text-3xl font-bold tracking-tight">{{ post.title }}</h1>
                <p v-if="post.excerpt" class="text-lg text-muted-foreground">{{ post.excerpt }}</p>
                <div class="markdown-content space-y-4 break-words leading-7 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:text-xl [&_h2]:font-semibold [&_img]:h-auto [&_img]:max-w-full [&_li]:ml-6 [&_li]:list-disc [&_ol_li]:list-decimal [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-950 [&_pre]:p-4 [&_pre]:text-slate-50 [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto" v-html="post.html"></div>
            </Card>
        </template>
    </article>
</template>
