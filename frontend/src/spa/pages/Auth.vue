<script>
import { api } from '../../api/api.js';
import { Alert } from '../components/ui/alert/index.js';
import { Button } from '../components/ui/button/index.js';
import { Card } from '../components/ui/card/index.js';
import { Input } from '../components/ui/input/index.js';

const titleKeys = {login: 'auth.loginTitle', register: 'auth.registerTitle'};

export default {
    components: { Alert, Button, Card, Input },
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
    }
};
</script>

<template>
    <section class="flex min-h-[calc(100vh-8rem)] items-center justify-center px-4 py-10 sm:px-6">
        <Card class="w-full max-w-md p-6 sm:p-8">
            <form class="flex flex-col gap-5" @submit.prevent="submit">
                <header class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">{{ $t('auth.account') }}</p>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                </header>

                <Alert v-if="error" variant="destructive" role="alert">{{ error }}</Alert>
                <Alert v-if="notice" role="status" class="border-emerald-500/50 bg-emerald-500/5 text-emerald-700 dark:text-emerald-400">{{ notice }}</Alert>

                <div v-if="mode === 'register'" class="space-y-2">
                    <label for="name" class="block text-sm font-medium">{{ $t('auth.name') }}</label>
                    <Input id="name" v-model="name" autocomplete="name" maxlength="120" required />
                </div>
                <div v-if="mode === 'login' || mode === 'register'" class="space-y-2">
                    <label for="email" class="block text-sm font-medium">{{ $t('auth.email') }}</label>
                    <Input id="email" v-model="email" type="email" autocomplete="email" required />
                </div>
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium">{{ $t('auth.password') }}</label>
                    <Input
                        id="password"
                        v-model="password"
                        type="password"
                        :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                        minlength="12"
                        required
                    />
                    <p class="text-sm text-muted-foreground">{{ $t('auth.passwordHint') }}</p>
                </div>

                <Button type="submit" :disabled="busy" class="w-full">
                    {{ busy ? $t('auth.submitting') : title }}
                </Button>
                <p class="text-center text-sm text-muted-foreground">
                    <router-link v-if="mode === 'login'" to="/register" class="text-primary underline-offset-4 hover:underline">{{ $t('auth.createAccount') }}</router-link>
                    <router-link v-else to="/login" class="text-primary underline-offset-4 hover:underline">{{ $t('auth.backToLogin') }}</router-link>
                </p>
            </form>
        </Card>
    </section>
</template>
