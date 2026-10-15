import { api } from '../services/api.js';

export default {
    data: () => ({ currentPassword: '', codes: [], error: '', busy: false }),
    async mounted() {
        try {
            const result = await api.get('/api/auth/me');
            if (!result.user) await this.$router.replace({path: '/login', query: {redirect: '/account/recovery-codes'}});
        } catch (error) {
            this.error = error.message;
        }
    },
    methods: {
        async regenerate() {
            this.error = ''; this.codes = []; this.busy = true;
            try {
                const result = await api.post('/api/auth/recovery-codes', { currentPassword: this.currentPassword });
                this.codes = result.codes;
                this.currentPassword = '';
            } catch (error) { this.error = error.message; }
            finally { this.busy = false; }
        }
    },
    template: `<section class="page"><h1>Códigos de recuperação</h1><p>Gere dez códigos para redefinir sua senha sem e-mail. Cada código funciona uma vez. Gerar um novo lote invalida todos os anteriores.</p><p v-if="error" class="alert alert-error" role="alert">{{ error }}</p><form v-if="codes.length === 0" class="card-form" @submit.prevent="regenerate"><label for="current-password">Confirme sua senha atual</label><input id="current-password" v-model="currentPassword" class="form-control" type="password" autocomplete="current-password" required><button :disabled="busy">{{ busy ? 'Gerando…' : 'Gerar códigos' }}</button></form><div v-else class="card-form" role="status"><p>Esta lista será exibida somente agora. Guarde-a em local seguro. Se perder todos os códigos e não puder entrar, um administrador deverá recuperar o acesso por procedimento controlado.</p><ol><li v-for="code in codes" :key="code"><code>{{ code }}</code></li></ol><button @click="codes = []">Ocultar códigos</button></div></section>`
};
