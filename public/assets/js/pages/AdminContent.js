import {api} from '../services/api.js';

export default {
    data: () => ({
        kind: 'posts',
        user: null,
        items: [],
        loading: false,
        error: '',
        notice: '',
        editingId: null,
        form: {title: '', slug: '', content: '', excerpt: ''},
        preview: false,
        previewHtml: ''
    }),
    computed: {
        base() {
            return `/api/admin/${this.kind}`;
        }, singular() {
            return this.kind === 'posts' ? 'Post' : 'Página';
        }
    },
    async mounted() {
        try {
            const result = await api.get('/api/auth/me');
            this.user = result.user;
            if (!this.user) {
                await this.$router.push({path: '/login', query: {redirect: '/admin/content'}});
                return;
            }
            if (this.user.role === 'ADMIN') await this.load();
        } catch (error) {
            this.error = error.message;
        }
    },
    methods: {
        async load() {
            this.loading = true;
            this.error = '';
            try {
                this.items = (await api.get(this.base)).items;
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
        edit(item) {
            this.editingId = item.id;
            this.form = {title: item.title, slug: item.slug, content: item.content, excerpt: item.excerpt || ''};
            this.preview = false;
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
        async save() {
            this.error = '';
            this.notice = '';
            try {
                await (this.editingId ? api.put(`${this.base}/${this.editingId}`, this.form) : api.post(this.base, this.form));
                this.notice = `${this.singular} salvo como rascunho.`;
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
                    this.previewHtml = (await api.post('/api/admin/markdown-preview', {content: this.form.content})).html;
                    this.preview = true;
                } catch (error) {
                    this.error = error.message;
                }
            } else this.preview = false;
        },
        async publish(item) {
            try {
                await api.post(`${this.base}/${item.id}/${item.status === 'PUBLISHED' ? 'unpublish' : 'publish'}`);
                await this.load();
            } catch (error) {
                this.error = error.message;
            }
        },
        async remove(item) {
            if (!window.confirm(`Excluir “${item.title}”?`)) return;
            try {
                await api.delete(`${this.base}/${item.id}`);
                await this.load();
            } catch (error) {
                this.error = error.message;
            }
        },
        async logout() {
            try {
                await api.post('/api/auth/logout');
                api.clearCsrf();
                this.user = null;
                this.items = [];
            } catch (error) {
                this.error = error.message;
            }
        }
    },
    template: `
      <section>
        <div class="header-row">
          <div><h1>Administração do conteúdo</h1>
            <p>Crie rascunhos em Markdown, revise e publique.</p></div>
          <div v-if="user" class="form-actions"><router-link class="button-link button-outline" to="/account/recovery-codes">Códigos de recuperação</router-link><button class="btn-secondary" @click="logout">Sair</button></div>
        </div>
        <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
        <template v-if="user && user.role !== 'ADMIN'"><p class="alert alert-error">Sua conta não tem acesso de
          administrador.</p></template>
        <template v-else-if="user">
          <div class="form-group"><label for="kind">Conteúdo</label><select id="kind" v-model="kind"
                                                                            class="form-control" @change="load">
            <option value="posts">Posts</option>
            <option value="pages">Pages</option>
          </select></div>
          <form class="card-form" @submit.prevent="save"><h2>{{ editingId ? 'Editar' : 'Novo' }} {{ singular }}</h2>
            <div class="form-group"><label for="title">Título</label><input id="title" v-model="form.title"
                                                                            class="form-control" maxlength="200"
                                                                            required></div>
            <div class="form-group"><label for="slug">Slug (opcional)</label><input id="slug" v-model="form.slug"
                                                                                    class="form-control"
                                                                                    pattern="[a-zA-Z0-9-]+"
                                                                                    placeholder="gerado-a-partir-do-titulo">
            </div>
            <div v-if="kind === 'posts'" class="form-group"><label for="excerpt">Resumo</label><textarea id="excerpt"
                                                                                                         v-model="form.excerpt"
                                                                                                         class="form-control"
                                                                                                         rows="2"
                                                                                                         maxlength="1000"></textarea>
            </div>
            <div class="form-group"><label for="content">Conteúdo em Markdown</label><textarea v-if="!preview"
                                                                                               id="content"
                                                                                               v-model="form.content"
                                                                                               class="form-control editor"
                                                                                               rows="12"
                                                                                               maxlength="60000"
                                                                                               required></textarea>
              <div v-else class="markdown-content preview" v-html="previewHtml"></div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="togglePreview">
                {{ preview ? 'Editar' : 'Pré-visualizar' }}
              </button>
              <button type="submit">Salvar conteúdo</button>
              <button v-if="editingId" type="button" class="btn-secondary" @click="reset">Cancelar</button>
            </div>
          </form>
          <p v-if="loading" class="loading">Carregando…</p>
          <p v-else-if="!items.length" class="empty-state">Nenhum conteúdo encontrado.</p>
          <div v-else class="table-wrap">
            <table>
              <thead>
              <tr>
                <th>Título</th>
                <th>Estado</th>
                <th>Ações</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="item in items" :key="item.id">
                <td>{{ item.title }}<small class="slug">/{{ kind === 'posts' ? 'blog/' : '' }}{{ item.slug }}</small>
                </td>
                <td>{{ item.status }}</td>
                <td class="table-actions">
                  <button class="btn-sm btn-secondary" @click="edit(item)">Editar</button>
                  <button class="btn-sm" @click="publish(item)">
                    {{ item.status === 'PUBLISHED' ? 'Despublicar' : 'Publicar' }}
                  </button>
                  <button class="btn-sm btn-danger" @click="remove(item)">Excluir</button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </template>
      </section>`
};
