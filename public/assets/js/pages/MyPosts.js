import {api} from '../services/api.js';

export default {
    data: () => ({items: [], loading: true, error: '', notice: '', editingId: null,
        form: {title: '', slug: '', content: '', excerpt: ''}, preview: false, previewHtml: ''}),
    async mounted() {
        await this.load();
    },
    methods: {
        async load() {
            this.loading = true;
            this.error = '';
            try {
                this.items = (await api.get('/api/me/posts')).items;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
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
                this.notice = 'Post salvo como rascunho.';
                this.reset();
                await this.load();
            } catch (error) {
                this.error = error.message;
            }
        },
        async togglePreview() {
            this.error = '';
            if (!this.preview) {
                try {
                    this.previewHtml = (await api.post('/api/me/markdown-preview', {content: this.form.content})).html;
                    this.preview = true;
                } catch (error) {
                    this.error = error.message;
                }
            } else this.preview = false;
        },
        async publish(post) {
            try {
                const action = post.status === 'PUBLISHED' ? 'unpublish' : 'publish';
                await api.post(`/api/me/posts/${post.id}/${action}`);
                await this.load();
            } catch (error) {
                this.error = error.message;
            }
        },
        async remove(post) {
            if (!window.confirm(`Excluir “${post.title}”?`)) return;
            try {
                await api.delete(`/api/me/posts/${post.id}`);
                await this.load();
            } catch (error) {
                this.error = error.message;
            }
        }
    },
    template: `
      <section>
        <h1>Meus Posts</h1>
        <p>Escreva em Markdown, revise a prévia e publique seus posts.</p>
        <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
        <form class="card-form" @submit.prevent="save"><h2>{{ editingId ? 'Editar' : 'Novo' }} Post</h2>
          <div class="form-group"><label for="title">Título</label><input id="title" v-model="form.title" class="form-control" maxlength="200" required></div>
          <div class="form-group"><label for="slug">Slug (opcional)</label><input id="slug" v-model="form.slug" class="form-control" pattern="[a-zA-Z0-9-]+" placeholder="gerado-a-partir-do-titulo"></div>
          <div class="form-group"><label for="excerpt">Resumo</label><textarea id="excerpt" v-model="form.excerpt" class="form-control" rows="2" maxlength="1000"></textarea></div>
          <div class="form-group"><label for="content">Conteúdo em Markdown</label><textarea v-if="!preview" id="content" v-model="form.content" class="form-control editor" rows="12" maxlength="60000" required></textarea><div v-else class="markdown-content preview" v-html="previewHtml"></div></div>
          <div class="form-actions"><button type="button" class="btn-secondary" @click="togglePreview">{{ preview ? 'Editar' : 'Pré-visualizar' }}</button><button type="submit">Salvar rascunho</button><button v-if="editingId" type="button" class="btn-secondary" @click="reset">Cancelar</button></div>
        </form>
        <p v-if="loading" class="loading">Carregando…</p><p v-else-if="!items.length" class="empty-state">Você ainda não criou posts.</p>
        <div v-else class="table-wrap"><table><thead><tr><th>Título</th><th>Estado</th><th>Ações</th></tr></thead><tbody>
          <tr v-for="post in items" :key="post.id"><td>{{ post.title }}<small class="slug">/blog/{{ post.slug }}</small></td><td>{{ post.status }}</td><td class="table-actions"><button class="btn-sm btn-secondary" @click="edit(post)">Editar</button><button class="btn-sm" @click="publish(post)">{{ post.status === 'PUBLISHED' ? 'Despublicar' : 'Publicar' }}</button><button class="btn-sm btn-danger" @click="remove(post)">Excluir</button></td></tr>
        </tbody></table></div>
      </section>`
};
