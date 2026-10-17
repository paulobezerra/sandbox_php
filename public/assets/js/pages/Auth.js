import {api} from '../services/api.js';

const titleKeys = {login: 'auth.loginTitle', register: 'auth.registerTitle'};

export default {
    data: () => ({name: '', email: '', password: '', error: '', notice: '', busy: false}),
    computed: {
        mode() {
            return this.$route.meta.authMode;
        }, title() {
            return this.$t(titleKeys[this.mode]);
        }
    },
    methods: {
        async submit() {
            this.error = '';
            this.notice = '';
            this.busy = true;
            try {
                if (this.mode === 'login') {
                    await api.post('/api/auth/login', {email: this.email, password: this.password});
                    await this.$router.push(this.$route.query.redirect || '/my-posts');
                    return;
                }
                if (this.mode === 'register') {
                    await api.post('/api/auth/register', {name: this.name, email: this.email, password: this.password});
                    this.notice = this.$t('auth.createdNotice');
                }
            } catch (error) {
                this.error = error.message;
            } finally {
                this.busy = false;
            }
        }
    },
    template: `
      <section class="auth-page">
        <form class="card-form" @submit.prevent="submit"><p class="eyebrow">{{ $t('auth.account') }}</p>
          <h1>{{ title }}</h1>
          <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
          <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>
          <div v-if="mode === 'register'" class="form-group"><label for="name">{{ $t('auth.name') }}</label><input id="name"
                                                                                                  v-model="name"
                                                                                                  class="form-control"
                                                                                                  autocomplete="name"
                                                                                                  maxlength="120"
                                                                                                  required></div>
          <div v-if="mode === 'login' || mode === 'register'" class="form-group"><label for="email">{{ $t('auth.email') }}</label><input
              id="email" v-model="email" class="form-control" type="email" autocomplete="email" required></div>
          <div class="form-group"><label for="password">{{ $t('auth.password') }}</label><input id="password"
                                                                                           v-model="password"
                                                                                           class="form-control"
                                                                                           type="password"
                                                                                           :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                                                                                           minlength="12"
                                                                                           required><small>{{ $t('auth.passwordHint') }}</small></div>
          <button :disabled="busy">{{ busy ? $t('auth.submitting') : title }}</button>
          <p class="auth-links">
            <router-link v-if="mode === 'login'" to="/register">{{ $t('auth.createAccount') }}</router-link>
            <router-link v-else to="/login">{{ $t('auth.backToLogin') }}</router-link>
          </p>
        </form>
      </section>`
};
