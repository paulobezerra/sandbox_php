import {api} from '../services/api.js';

const titles = {login: 'Entrar', register: 'Criar conta', forgot: 'Recuperar senha', reset: 'Redefinir senha'};

export default {
    data: () => ({name: '', email: '', password: '', code: '', user: null, error: '', notice: '', busy: false}),
    computed: {
        mode() {
            return this.$route.meta.authMode;
        }, title() {
            return titles[this.mode];
        }
    },
    methods: {
        async submit() {
            this.error = '';
            this.notice = '';
            this.busy = true;
            try {
                if (this.mode === 'login') {
                    const result = await api.post('/api/auth/login', {email: this.email, password: this.password});
                    this.user = result.user;
                    if (this.user?.role === 'ADMIN') {
                        await this.$router.push(this.$route.query.redirect === '/account/recovery-codes' ? '/account/recovery-codes' : '/admin/content');
                        return;
                    }
                    if (this.$route.query.redirect === '/account/recovery-codes') {
                        await this.$router.push('/account/recovery-codes');
                        return;
                    }
                    this.notice = 'Sessão iniciada. Você pode gerar seus códigos de recuperação agora.';
                    return;
                }
                if (this.mode === 'register') {
                    await api.post('/api/auth/register', {name: this.name, email: this.email, password: this.password});
                    this.notice = 'Conta criada. O acesso à administração exige uma conta ADMIN.';
                    return;
                }
                if (this.mode === 'forgot') return;
                await api.post('/api/auth/reset-password', {code: this.code, password: this.password});
                api.clearCsrf();
                this.notice = 'Senha redefinida. Todas as sessões anteriores foram encerradas; você já pode entrar.';
            } catch (error) {
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        }
    },
    template: `
      <section class="auth-page">
        <form v-if="mode !== 'forgot'" class="card-form" @submit.prevent="submit"><p class="eyebrow">CONTA</p>
          <h1>{{ title }}</h1>
          <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
          <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
          <router-link v-if="mode === 'login' && user?.role === 'USER'" to="/account/recovery-codes">Gerar códigos de recuperação</router-link>
          <div v-if="mode === 'register'" class="form-group"><label for="name">Nome</label><input id="name"
                                                                                                  v-model="name"
                                                                                                  class="form-control"
                                                                                                  autocomplete="name"
                                                                                                  maxlength="120"
                                                                                                  required></div>
          <div v-if="mode === 'login' || mode === 'register'" class="form-group"><label for="email">E-mail</label><input
              id="email" v-model="email" class="form-control" type="email" autocomplete="email" required></div>
          <div v-if="mode === 'reset'" class="form-group"><label for="code">Código de recuperação</label><input
              id="code" v-model="code" class="form-control" autocomplete="off" minlength="32" maxlength="32" required>
          </div>
          <div v-if="mode === 'login' || mode === 'register' || mode === 'reset'" class="form-group"><label
              for="password">{{ mode === 'reset' ? 'Nova senha' : 'Senha' }}</label><input id="password"
                                                                                           v-model="password"
                                                                                           class="form-control"
                                                                                           type="password"
                                                                                           :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                                                                                           minlength="12"
                                                                                           required><small>Use pelo
            menos 12 caracteres.</small></div>
          <button :disabled="busy">{{ busy ? 'Enviando…' : title }}</button>
          <p class="auth-links">
            <router-link v-if="mode === 'login'" to="/forgot-password">Esqueci minha senha</router-link>
            <router-link v-if="mode === 'login'" to="/register">Criar conta</router-link>
            <router-link v-if="mode !== 'login'" to="/login">Voltar para entrar</router-link>
          </p>
        </form>
        <section v-else class="card-form"><p class="eyebrow">CONTA</p>
          <h1>{{ title }}</h1>
          <p>A recuperação usa códigos gerados após entrar na conta. Se você ainda está conectado,
            <router-link to="/account/recovery-codes">gere ou renove seus códigos</router-link>
            . Se perdeu o acesso e todos os códigos, consulte o procedimento de recuperação verificada do operador. Se
            esse procedimento não foi definido, o acesso não pode ser recuperado.
          </p>
          <router-link to="/reset-password">Tenho um código de recuperação</router-link>
        </section>
      </section>`
};
