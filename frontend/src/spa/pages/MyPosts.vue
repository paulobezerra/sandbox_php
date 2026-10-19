<script>
import { api } from '../../api/api.js';
import { Alert } from '../components/ui/alert/index.js';
import { Button } from '../components/ui/button/index.js';
import { Card } from '../components/ui/card/index.js';
import { Input } from '../components/ui/input/index.js';
import { Textarea } from '../components/ui/textarea/index.js';

export default {
    components: { Alert, Button, Card, Input, Textarea },
    data: () => ({ items: [], loading: true, error: '', notice: '', editingId: null,
        form: {title: '', slug: '', content: '', excerpt: ''}, preview: false, previewHtml: '', page: 1, total: 0, perPage: 10}),
    computed: { pages() { return Math.max(1, Math.ceil(this.total / this.perPage)); } },
    async mounted() { await this.load(); },
    methods: {
        translate(key, values = {}) {
            return Object.entries(values).reduce((text, [name, value]) => text.replace(`{${name}}`, String(value)), this.$t(key));
        },
        async load(page = this.page) {
            this.loading = true;
            this.error = '';
            try {
                const result = await api.get(`/api/me/posts?page=${page}&perPage=${this.perPage}`);
                this.items = result.items;
                this.page = result.page;
                this.total = result.total;
            } catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        reset() {
            this.editingId = null;
            this.form = {title: '', slug: '', content: '', excerpt: ''};
            this.preview = false;
        },
        edit(post) {
            this.editingId = post.id;
            this.form = {title: post.title, slug: post.slug, content: post.content, excerpt: post.excerpt || ''};
            this.preview = false;
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
        async save() {
            this.error = '';
            this.notice = '';
            try {
                await (this.editingId
                    ? api.put(`/api/me/posts/${this.editingId}`, this.form)
                    : api.post('/api/me/posts', this.form));
                this.notice = this.$t('post.savedNotice');
                this.reset();
                await this.load();
            } catch (error) { this.error = error.message; }
        },
        async togglePreview() {
            this.error = '';
            if (!this.preview) {
                try {
                    this.previewHtml = (await api.post('/api/me/markdown-preview', {content: this.form.content})).html;
                    this.preview = true;
                } catch (error) { this.error = error.message; }
            } else this.preview = false;
        },
        async publish(post) {
            try {
                const action = post.status === 'PUBLISHED' ? 'unpublish' : 'publish';
                await api.post(`/api/me/posts/${post.id}/${action}`);
                await this.load();
            } catch (error) { this.error = error.message; }
        },
        async remove(post) {
            if (!window.confirm(this.translate('post.deleteConfirm', {title: post.title}))) return;
            try {
                await api.delete(`/api/me/posts/${post.id}`);
                await this.load();
            } catch (error) { this.error = error.message; }
        }
    }
};
</script>

<template>
    <section class="mx-auto w-full max-w-5xl space-y-6">
      <header class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">{{ $t('post.myPostsTitle') }}</h1>
        <p class="text-muted-foreground">{{ $t('post.editorIntro') }}</p>
      </header>
      <Alert v-if="error" variant="destructive" role="alert">{{ error }}</Alert>
      <Alert v-if="notice" role="status" class="border-green-600/30 bg-green-600/10 text-green-800 dark:text-green-300">{{ notice }}</Alert>

      <Card class="p-6">
        <form class="space-y-5" @submit.prevent="save">
          <h2 class="text-xl font-semibold">{{ $t(editingId ? 'post.edit' : 'post.new') }} {{ $t('post.title') }}</h2>
          <div class="space-y-2">
            <label for="title" class="block text-sm font-medium">{{ $t('post.title') }}</label>
            <Input id="title" v-model="form.title" maxlength="200" required />
          </div>
          <div class="space-y-2">
            <label for="slug" class="block text-sm font-medium">{{ $t('post.slug') }}</label>
            <Input id="slug" v-model="form.slug" pattern="[a-zA-Z0-9-]+" :placeholder="$t('post.slugPlaceholder')" />
          </div>
          <div class="space-y-2">
            <label for="excerpt" class="block text-sm font-medium">{{ $t('post.excerpt') }}</label>
            <Textarea id="excerpt" v-model="form.excerpt" rows="2" maxlength="1000" />
          </div>
          <div class="space-y-2">
            <label for="content" class="block text-sm font-medium">{{ $t('post.markdownContent') }}</label>
            <Textarea v-if="!preview" id="content" v-model="form.content" class="min-h-72 font-mono" rows="12" maxlength="60000" required />
            <div v-else class="markdown-content preview space-y-4 break-words rounded-md border bg-muted/30 p-4 leading-7 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_h1]:text-2xl [&_h1]:font-bold [&_h2]:text-xl [&_h2]:font-semibold [&_img]:h-auto [&_img]:max-w-full [&_li]:ml-6 [&_li]:list-disc [&_ol_li]:list-decimal [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-950 [&_pre]:p-4 [&_pre]:text-slate-50 [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto" v-html="previewHtml"></div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button type="button" variant="outline" @click="togglePreview">{{ preview ? $t('post.edit') : $t('post.preview') }}</Button>
            <Button type="submit">{{ $t('post.saveDraft') }}</Button>
            <Button v-if="editingId" type="button" variant="secondary" @click="reset">{{ $t('post.cancel') }}</Button>
          </div>
        </form>
      </Card>

      <p v-if="loading" class="py-8 text-center text-sm text-muted-foreground" role="status">{{ $t('post.loading') }}</p>
      <p v-else-if="!items.length" class="rounded-lg border border-dashed px-6 py-10 text-center text-muted-foreground">{{ $t('post.emptyMine') }}</p>
      <div v-else class="overflow-hidden rounded-lg border">
        <div class="overflow-x-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="bg-muted/50 [&_tr]:border-b">
              <tr class="text-left">
                <th class="h-11 px-4 font-medium">{{ $t('post.title') }}</th>
                <th class="h-11 px-4 font-medium">{{ $t('post.status') }}</th>
                <th class="h-11 px-4 font-medium">{{ $t('post.actions') }}</th>
              </tr>
            </thead>
            <tbody class="[&_tr:last-child]:border-0">
              <tr v-for="post in items" :key="post.id" class="border-b transition-colors hover:bg-muted/30">
                <td class="px-4 py-3 font-medium">
                  {{ post.title }}
                  <small class="block font-normal text-muted-foreground">/blog/{{ post.slug }}</small>
                </td>
                <td class="px-4 py-3 text-muted-foreground">{{ post.status === 'PUBLISHED' ? $t('post.published') : $t('post.draft') }}</td>
                <td class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" @click="edit(post)">{{ $t('post.edit') }}</Button>
                    <Button size="sm" @click="publish(post)">{{ post.status === 'PUBLISHED' ? $t('post.unpublish') : $t('post.publish') }}</Button>
                    <Button size="sm" variant="destructive" @click="remove(post)">{{ $t('post.delete') }}</Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <nav v-if="pages > 1" class="flex items-center justify-between gap-4" :aria-label="$t('post.myPostsTitle')">
        <Button variant="outline" :disabled="page <= 1" @click="load(page - 1)">{{ $t('post.previous') }}</Button>
        <span class="text-sm text-muted-foreground">{{ translate('post.pageLabel', {page, pages}) }}</span>
        <Button variant="outline" :disabled="page >= pages" @click="load(page + 1)">{{ $t('post.next') }}</Button>
      </nav>
    </section>
</template>
