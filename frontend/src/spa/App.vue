<script>
import { api } from '../api/api.js';
import { Menu, X } from '@lucide/vue';
import { Sidebar, SidebarProvider } from './components/ui/sidebar';
import { Button } from './components/ui/button';

export default {
    components: {Sidebar, SidebarProvider, Menu, X, Button},
    data: () => ({user: null, sidebarOpen: false}),
    async mounted() {
        window.addEventListener('auth-changed', this.onAuthChanged);
        try {
            this.user = (await api.get('/api/auth/me')).user;
        } catch {
            this.user = null;
        }
    },
    beforeUnmount() {
        window.removeEventListener('auth-changed', this.onAuthChanged);
    },
    methods: {
        onAuthChanged(event) {
            this.user = event.detail;
        },
        async logout() {
            try {
                await api.post('/api/auth/logout');
                api.clearCsrf();
                if (this.$route.path === '/my-posts') await this.$router.push('/');
            } catch (error) {
                window.alert(error.message);
            }
        }
    }
};
</script>

<template>
    <SidebarProvider :open="sidebarOpen" @update:open="sidebarOpen = $event">
        <header class="shell-toolbar sticky top-0 z-50 flex h-16 w-full shrink-0 items-center gap-3 border-b border-sidebar-border bg-sidebar px-4 text-sidebar-foreground md:px-6">
            <Button
                variant="ghost"
                size="icon"
                class="sidebar-toggle shrink-0 text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                type="button"
                :aria-label="$t(sidebarOpen ? 'shell.closeMenu' : 'shell.openMenu')"
                aria-controls="application-sidebar"
                :aria-expanded="sidebarOpen"
                @click="sidebarOpen = !sidebarOpen"
            >
                <Menu v-if="!sidebarOpen" aria-hidden="true" :size="20" />
                <X v-else aria-hidden="true" :size="20" />
            </Button>
            <a class="text-lg font-bold text-sidebar-foreground no-underline" href="/">Sandbox PHP</a>
        </header>

        <div class="flex min-h-0 flex-1 max-[640px]:flex-col">
            <Sidebar>
                <nav class="flex flex-col gap-1 max-[640px]:flex-row max-[640px]:flex-wrap">
                    <router-link
                        to="/"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground no-underline transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        active-class="font-semibold text-white"
                    >{{ $t('shell.home') }}</router-link>
                    <router-link
                        to="/blog"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground no-underline transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        active-class="font-semibold text-white"
                    >{{ $t('shell.blog') }}</router-link>
                    <router-link
                        v-if="!user"
                        to="/login"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground no-underline transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        active-class="font-semibold text-white"
                    >{{ $t('shell.login') }}</router-link>
                    <router-link
                        v-if="user"
                        to="/my-posts"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground no-underline transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        active-class="font-semibold text-white"
                    >{{ $t('shell.myPosts') }}</router-link>
                    <Button
                        v-if="user"
                        variant="ghost"
                        class="w-full justify-start px-3 text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        type="button"
                        @click="logout"
                    >{{ $t('shell.logout') }}</Button>
                </nav>
            </Sidebar>

            <main
                class="min-w-0 flex-1 transition-[margin] duration-200 ease-in-out motion-reduce:transition-none"
                :class="sidebarOpen ? 'ml-64 max-[640px]:ml-0' : 'ml-0'"
            >
                <div class="px-6 py-8 md:px-10">
                    <router-view />
                    <footer class="mt-16 flex flex-wrap justify-between gap-4 border-t border-border pt-5 text-sm text-muted-foreground max-[640px]:flex-col"><span>Sandbox PHP · PHP 8.4 + Slim 4 + Vue 3</span><a class="text-primary underline-offset-4 hover:underline" href="/docs">{{ $t('shell.footer') }}</a></footer>
                </div>
            </main>
        </div>
    </SidebarProvider>
</template>
