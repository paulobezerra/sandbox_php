import { api } from '../services/api.js';

export default {
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
    },
    template: `
      <section>
        <h1>{{ $t('post.myPostsTitle') }}</h1>
        <p>{{ $t('post.editorIntro') }}</p>
        <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
        <form class="card-form" @submit.prevent="save"><h2>{{ $t(editingId ? 'post.edit' : 'post.new') }} {{ $t('post.title') }}</h2>
          <div class="form-group"><label for="title">{{ $t('post.title') }}</label><input id="title" v-model="form.title" class="form-control" maxlength="200" required></div>
          <div class="form-group"><label for="slug">{{ $t('post.slug') }}</label><input id="slug" v-model="form.slug" class="form-control" pattern="[a-zA-Z0-9-]+" :placeholder="$t('post.slugPlaceholder')"></div>
          <div class="form-group"><label for="excerpt">{{ $t('post.excerpt') }}</label><textarea id="excerpt" v-model="form.excerpt" class="form-control" rows="2" maxlength="1000"></textarea></div>
          <div class="form-group"><label for="content">{{ $t('post.markdownContent') }}</label><textarea v-if="!preview" id="content" v-model="form.content" class="form-control editor" rows="12" maxlength="60000" required></textarea><div v-else class="markdown-content preview" v-html="previewHtml"></div></div>
          <div class="form-actions"><button type="button" class="btn-secondary" @click="togglePreview">{{ preview ? $t('post.edit') : $t('post.preview') }}</button><button type="submit">{{ $t('post.saveDraft') }}</button><button v-if="editingId" type="button" class="btn-secondary" @click="reset">{{ $t('post.cancel') }}</button></div>
        </form>
        <p v-if="loading" class="loading">{{ $t('post.loading') }}</p><p v-else-if="!items.length" class="empty-state">{{ $t('post.emptyMine') }}</p>
        <div v-else class="table-wrap"><table><thead><tr><th>{{ $t('post.title') }}</th><th>{{ $t('post.status') }}</th><th>{{ $t('post.actions') }}</th></tr></thead><tbody>
          <tr v-for="post in items" :key="post.id"><td>{{ post.title }}<small class="slug">/blog/{{ post.slug }}</small></td><td>{{ post.status === 'PUBLISHED' ? $t('post.published') : $t('post.draft') }}</td><td class="table-actions"><button class="btn-sm btn-secondary" @click="edit(post)">{{ $t('post.edit') }}</button><button class="btn-sm" @click="publish(post)">{{ post.status === 'PUBLISHED' ? $t('post.unpublish') : $t('post.publish') }}</button><button class="btn-sm btn-danger" @click="remove(post)">{{ $t('post.delete') }}</button></td></tr>
        </tbody></table></div>
        <nav v-if="pages > 1" class="pagination" :aria-label="$t('post.myPostsTitle')"><button class="btn-secondary" :disabled="page <= 1" @click="load(page - 1)">{{ $t('post.previous') }}</button><span>{{ translate('post.pageLabel', {page, pages}) }}</span><button class="btn-secondary" :disabled="page >= pages" @click="load(page + 1)">{{ $t('post.next') }}</button></nav>
      </section>`
};
